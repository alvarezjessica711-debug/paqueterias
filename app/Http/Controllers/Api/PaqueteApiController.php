<?php

namespace App\Http\Controllers\Api;

use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\PaqueteAuditoria;
use App\Models\Residente;
use App\Notifications\NuevoPaqueteNotification;
use App\Services\WhatsAppDeliveryCodeService;
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

        $query = Paquete::query()->with(['residente', 'casa', 'entregadoPor', 'autorizacionActiva']);
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

    public function history(Request $request, Paquete $paquete): JsonResponse
    {
        if ($request->user()->rol === 'residente' && $paquete->residente_id !== $request->user()->residente_id) {
            return $this->error('No autorizado para consultar este paquete.', 403);
        }

        return response()->json(['data' => $paquete->auditorias()->with('usuario:id,name')->get()->map(fn (PaqueteAuditoria $evento) => [
            'id' => $evento->id,
            'paquete_id' => $evento->paquete_id,
            'usuario' => $evento->usuario?->name,
            'accion' => $evento->accion,
            'campo' => $evento->campo,
            'valor_anterior' => $evento->valor_anterior,
            'valor_nuevo' => $evento->valor_nuevo,
            'fecha' => $evento->created_at?->toISOString(),
        ])]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->exists('casa_id') || $request->exists('apartamento_id')) {
            $request->merge(['casa_id' => $request->input('casa_id', $request->input('apartamento_id'))]);
        }
        $data = $request->validate([
            'residente_id' => ['required', 'integer', 'exists:residentes,id'],
            'casa_id' => ['required', 'integer', 'exists:apartamentos,id'],
            'empresa' => ['required', 'string', 'max:255'],
            'destinatario' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'guia' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'detalles' => ['nullable', 'string'],
        ], [], ['casa_id' => 'código de casa']);

        $residente = Residente::query()->with('casas')->findOrFail($data['residente_id']);
        $casa = $residente->casas()->where('activo', true)->find($data['casa_id']);
        if (! $residente->activo || $casa === null) {
            throw ValidationException::withMessages(['casa_id' => 'La casa activa seleccionada no pertenece al residente.']);
        }
        unset($data['casa_id']);
        $data['apartamento_id'] = $casa->id;

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('paquetes', 'public');
        }

        $data['direccion'] = $this->direccionValidada($data['direccion'] ?? $casa->codigo, $casa->codigo);
        $paquete = DB::transaction(function () use ($data, $request): Paquete {
            $paquete = Paquete::query()->create([...$data, 'estado' => 'Pendiente']);
            $this->registrarAuditoria($paquete, $request->user()->id, 'registrado', [
                'destinatario' => [null, $paquete->destinatario],
                'direccion' => [null, $paquete->direccion],
                'estado' => [null, $paquete->estado],
            ]);

            return $paquete;
        });
        $notificado = false;
        $estadoWhatsApp = app(WhatsAppDeliveryCodeService::class)->send($paquete);
        $paquete->load(['residente.user', 'casa']);
        $user = $paquete->residente?->user;
        if ($user !== null && filled($user->email)) {
            try {
                $user->notify(new NuevoPaqueteNotification($paquete));
                $notificado = true;
            } catch (\Throwable) {
                Log::warning('No fue posible enviar la notificación de nuevo paquete.', ['paquete_id' => $paquete->id]);
            }
        }

        return response()->json([
            'data' => $this->paqueteJson($paquete),
            'notificacion_enviada' => $notificado,
            'whatsapp_estado' => $estadoWhatsApp,
            'codigo_entrega' => $estadoWhatsApp === 'accepted' ? null : $paquete->codigo_entrega,
        ], 201);
    }

    public function update(Request $request, Paquete $paquete): JsonResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return $this->error('Solo se pueden actualizar paquetes pendientes.', 409);
        }

        if ($request->exists('casa_id') || $request->exists('apartamento_id')) {
            $request->merge(['casa_id' => $request->input('casa_id', $request->input('apartamento_id'))]);
        }
        $data = $request->validate([
            'residente_id' => ['sometimes', 'required', 'integer', 'exists:residentes,id'],
            'casa_id' => ['sometimes', 'required', 'integer', 'exists:apartamentos,id'],
            'empresa' => ['sometimes', 'required', 'string', 'max:255'],
            'destinatario' => ['sometimes', 'required', 'string', 'max:255'],
            'direccion' => ['sometimes', 'required', 'string', 'max:255'],
            'guia' => ['sometimes', 'required', 'string', 'max:100'],
            'detalles' => ['sometimes', 'nullable', 'string'],
            'estado' => ['prohibited'],
            'entregado_por' => ['prohibited'],
            'fecha_entrega' => ['prohibited'],
            'recibido_por' => ['prohibited'],
        ], [], ['casa_id' => 'código de casa']);

        if (array_key_exists('casa_id', $data)) {
            $data['apartamento_id'] = $data['casa_id'];
            unset($data['casa_id']);
        }

        $residenteId = $data['residente_id'] ?? $paquete->residente_id;
        $apartamentoId = $data['apartamento_id'] ?? $paquete->apartamento_id;
        if (array_key_exists('residente_id', $data) || array_key_exists('apartamento_id', $data)) {
            $residente = Residente::query()->with('casas')->findOrFail($residenteId);
            $casa = $residente->casas()->where('activo', true)->find($apartamentoId);
            if (! $residente->activo || $casa === null) {
                throw ValidationException::withMessages(['casa_id' => 'La casa activa seleccionada no pertenece al residente.']);
            }
        } else {
            $residente = $paquete->residente;
            $casa = $paquete->casa;
        }

        if (array_key_exists('direccion', $data)) {
            $data['direccion'] = $this->direccionValidada($data['direccion'], $casa?->codigo ?? '');
        } elseif (array_key_exists('apartamento_id', $data) || array_key_exists('residente_id', $data)) {
            $data['direccion'] = $casa?->codigo ?? '';
        }
        if (! array_key_exists('destinatario', $data) && (array_key_exists('residente_id', $data))) {
            $data['destinatario'] = $residente?->nombre;
        }

        $anteriores = $paquete->only(array_keys($data));
        DB::transaction(function () use ($paquete, $data, $anteriores, $request): void {
            $paquete->update($data);
            $cambios = [];
            foreach ($data as $campo => $valor) {
                if (($anteriores[$campo] ?? null) != $valor) {
                    $cambios[$campo] = [$anteriores[$campo] ?? null, $valor];
                }
            }
            $this->registrarAuditoria($paquete, $request->user()->id, 'modificado', $cambios);
        });
        $paquete->load(['residente', 'casa', 'entregadoPor', 'autorizacionActiva']);

        return response()->json(['data' => $this->paqueteJson($paquete)]);
    }

    private function direccionValidada(?string $direccion, string $casaCodigo): string
    {
        return filled($direccion) ? trim($direccion) : $casaCodigo;
    }

    private function registrarAuditoria(Paquete $paquete, int $usuarioId, string $accion, array $cambios): void
    {
        foreach ($cambios as $campo => [$anterior, $nuevo]) {
            PaqueteAuditoria::query()->create([
                'paquete_id' => $paquete->id,
                'user_id' => $usuarioId,
                'accion' => $accion,
                'campo' => $campo,
                'valor_anterior' => is_scalar($anterior) ? (string) $anterior : null,
                'valor_nuevo' => is_scalar($nuevo) ? (string) $nuevo : null,
                'created_at' => now(),
            ]);
        }
    }

    public function validateDeliveryCode(Request $request, Paquete $paquete): JsonResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return $this->error('Este paquete ya fue entregado.', 409);
        }

        $data = $request->validate([
            'codigo_entrega' => ['required', 'string', 'max:10'],
        ]);

        $valid = DB::transaction(function () use ($paquete, $request, $data): bool {
            $locked = Paquete::query()->with(['residente', 'casa'])->lockForUpdate()->find($paquete->id);
            if ($locked === null || $locked->estado !== 'Pendiente' || $locked->codigo_entrega_usado_en !== null) {
                return false;
            }

            $recipient = trim((string) ($locked->destinatario ?: $locked->residente?->nombre));
            if ($locked->casa === null || $recipient === '' || blank($locked->codigo_entrega)) {
                return false;
            }

            if (! hash_equals((string) $locked->codigo_entrega, strtoupper(trim($data['codigo_entrega'])))) {
                $locked->forceFill([
                    'codigo_entrega_validado_por' => null,
                    'codigo_entrega_validado_at' => null,
                ])->save();
                return false;
            }

            $locked->forceFill([
                'codigo_entrega_validado_por' => $request->user()->id,
                'codigo_entrega_validado_at' => now(),
            ])->save();

            return true;
        });

        if (! $valid) {
            if ($paquete->fresh()->estado !== 'Pendiente') {
                return $this->error('Este paquete ya fue entregado.', 409);
            }

            return $this->error('El código no es válido para este paquete, o falta la casa o el destinatario registrado.', 422);
        }

        return response()->json(['message' => 'Código válido para este paquete.', 'codigo_validado' => true]);
    }

    public function deliver(Request $request, Paquete $paquete): JsonResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return $this->error('Este paquete ya fue entregado.', 409);
        }

        $data = $request->validate([
            'tipo_receptor' => ['required', 'in:residente,autorizado'],
        ]);

        $delivered = DB::transaction(function () use ($paquete, $request, $data): bool {
            $locked = Paquete::query()->with(['residente', 'casa', 'autorizacionActiva'])->lockForUpdate()->find($paquete->id);
            if ($locked === null || $locked->estado !== 'Pendiente') {
                return false;
            }

            $validatedByCurrentGuard = $locked->codigo_entrega_validado_por === $request->user()->id
                && $locked->codigo_entrega_validado_at?->greaterThan(now()->subMinutes(5));
            $recipient = trim((string) ($locked->destinatario ?: $locked->residente?->nombre));
            if (! $validatedByCurrentGuard || $locked->codigo_entrega_usado_en !== null || blank($locked->codigo_entrega)
                || $locked->casa === null || $recipient === '') {
                return false;
            }

            $authorization = $locked->autorizacionActiva;
            $receiver = $recipient;
            if ($data['tipo_receptor'] === 'autorizado') {
                if ($authorization === null) {
                    throw ValidationException::withMessages(['tipo_receptor' => 'No existe una autorización activa para este paquete.']);
                }
                $receiver = $authorization->nombre_autorizado;
                $authorization->update(['estado' => AutorizacionRetiro::UTILIZADA]);
            } elseif ($authorization !== null) {
                $authorization->update(['estado' => AutorizacionRetiro::CANCELADA]);
            }

            $deliveredAt = now();
            $locked->update([
                'estado' => 'Entregado',
                'entregado_por' => $request->user()->id,
                'fecha_entrega' => $deliveredAt,
                'recibido_por' => $receiver,
                'codigo_entrega_validado_por' => null,
                'codigo_entrega_validado_at' => null,
                'codigo_entrega_usado_en' => $deliveredAt,
            ]);
            $this->registrarAuditoria($locked, $request->user()->id, 'entregado', [
                'estado' => ['Pendiente', 'Entregado'],
                'entregado_por' => [null, $request->user()->id],
                'fecha_entrega' => [null, $deliveredAt->toISOString()],
                'recibido_por' => [null, $receiver],
                'codigo_entrega_usado_en' => [null, $deliveredAt->toISOString()],
            ]);

            return true;
        });

        if (! $delivered) {
            if ($paquete->fresh()->estado !== 'Pendiente') {
                return $this->error('Este paquete ya fue entregado.', 409);
            }

            return $this->error('Valida primero el código de entrega de este paquete. La validación vence en cinco minutos.', 409);
        }

        $paqueteEntregado = $paquete->fresh();
        $paqueteEntregado->load(['residente', 'casa', 'entregadoPor', 'autorizacionActiva']);

        return response()->json(['message' => 'Paquete entregado correctamente.', 'data' => $this->paqueteJson($paqueteEntregado)]);
    }

}
