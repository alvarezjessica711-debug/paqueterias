<?php

namespace App\Http\Controllers\Api;

use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\Residente;
use App\Notifications\NuevoPaqueteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaqueteApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'estado' => ['nullable', 'in:Pendiente,Entregado'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Paquete::query()->with(['residente', 'apartamento', 'entregadoPor', 'autorizacionActiva']);
        if ($request->user()->rol === 'residente') {
            abort_if($request->user()->residente_id === null, 403, 'La cuenta no tiene residente asociado.');
            $query->where('residente_id', $request->user()->residente_id);
        }

        $paquetes = $query
            ->when($filters['estado'] ?? null, fn ($builder, $estado) => $builder->where('estado', $estado))
            ->when($filters['fecha_desde'] ?? null, fn ($builder, $fecha) => $builder->whereDate('created_at', '>=', $fecha))
            ->when($filters['fecha_hasta'] ?? null, fn ($builder, $fecha) => $builder->whereDate('created_at', '<=', $fecha))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->through(fn (Paquete $paquete) => $this->paqueteJson($paquete));

        return response()->json($paquetes);
    }

    public function show(Request $request, Paquete $paquete): JsonResponse
    {
        if ($request->user()->rol === 'residente' && $paquete->residente_id !== $request->user()->residente_id) {
            return $this->error('No autorizado para consultar este paquete.', 403);
        }

        return response()->json(['data' => $this->paqueteJson($paquete)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'residente_id' => ['required', 'integer', 'exists:residentes,id'],
            'apartamento_id' => ['required', 'integer', 'exists:apartamentos,id'],
            'empresa' => ['required', 'string', 'max:255'],
            'guia' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'detalles' => ['nullable', 'string'],
        ]);

        $residente = Residente::query()->with('apartamentos')->findOrFail($data['residente_id']);
        $apartamento = $residente->apartamentos()->where('activo', true)->find($data['apartamento_id']);
        if (! $residente->activo || $apartamento === null) {
            throw ValidationException::withMessages(['apartamento_id' => 'El apartamento activo seleccionado no pertenece al residente.']);
        }

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('paquetes', 'public');
        }

        $paquete = Paquete::query()->create([...$data, 'estado' => 'Pendiente']);
        $notificado = false;
        $paquete->load(['residente.user', 'apartamento']);
        $user = $paquete->residente?->user;
        if ($user !== null && filled($user->email)) {
            try {
                $user->notify(new NuevoPaqueteNotification($paquete));
                $notificado = true;
            } catch (\Throwable) {
                Log::warning('No fue posible enviar la notificación de nuevo paquete.', ['paquete_id' => $paquete->id]);
            }
        }

        return response()->json(['data' => $this->paqueteJson($paquete), 'notificacion_enviada' => $notificado], 201);
    }

    public function deliver(Request $request, Paquete $paquete): JsonResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return $this->error('Este paquete ya fue entregado.', 409);
        }

        $data = $request->validate([
            'tipo_receptor' => ['required', 'in:residente,autorizado'],
            'firma' => ['required', 'string', 'max:2097152', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! $this->firmaTieneTrazo($value)) {
                    $fail('Debes registrar una firma válida antes de confirmar la entrega.');
                }
            }],
        ]);

        $delivered = DB::transaction(function () use ($paquete, $request, $data): bool {
            $locked = Paquete::query()->with(['residente', 'autorizacionActiva'])->lockForUpdate()->find($paquete->id);
            if ($locked === null || $locked->estado !== 'Pendiente') {
                return false;
            }
            if ($locked->residente === null) {
                throw ValidationException::withMessages(['entrega' => 'No fue posible identificar al residente.']);
            }

            $authorization = $locked->autorizacionActiva;
            $receiver = $locked->residente->nombre;
            if ($data['tipo_receptor'] === 'autorizado') {
                if ($authorization === null) {
                    throw ValidationException::withMessages(['tipo_receptor' => 'No existe una autorización activa para este paquete.']);
                }
                $receiver = $authorization->nombre_autorizado;
                $authorization->update(['estado' => AutorizacionRetiro::UTILIZADA]);
            } elseif ($authorization !== null) {
                $authorization->update(['estado' => AutorizacionRetiro::CANCELADA]);
            }

            $locked->update([
                'estado' => 'Entregado', 'firma' => $data['firma'], 'entregado_por' => $request->user()->id,
                'fecha_entrega' => now(), 'recibido_por' => $receiver,
            ]);
            return true;
        });

        if (! $delivered) {
            return $this->error('Este paquete ya fue entregado.', 409);
        }

        return response()->json(['message' => 'Paquete entregado correctamente.', 'data' => $this->paqueteJson($paquete->fresh())]);
    }

    private function firmaTieneTrazo(string $firma): bool
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $firma, $matches)) {
            return false;
        }
        $binary = base64_decode($matches[1], true);
        if ($binary === false) {
            return false;
        }
        $dimensions = @getimagesizefromstring($binary);
        if ($dimensions === false || $dimensions[2] !== IMAGETYPE_PNG || $dimensions[0] * $dimensions[1] > 2_000_000) {
            return false;
        }
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) < 127) {
                    imagedestroy($image);
                    return true;
                }
            }
        }
        imagedestroy($image);
        return false;
    }
}
