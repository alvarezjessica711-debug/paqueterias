<?php

namespace App\Http\Controllers;

use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\Residente;
use App\Notifications\NuevoPaqueteNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaqueteController extends Controller
{
    public function create(): View
    {
        $residentes = Residente::query()
            ->where('activo', true)
            ->with(['apartamentos' => fn ($query) => $query->where('activo', true)])
            ->orderBy('nombre')
            ->get();
        $residentesParaFormulario = $residentes->map(fn (Residente $residente) => [
            'id' => $residente->id,
            'etiqueta' => $residente->nombre.' (ID #'.$residente->id.')',
            'apartamentos' => $residente->apartamentos->map(fn ($apartamento) => [
                'id' => $apartamento->id,
                'texto' => $apartamento->torre.' - Apto. '.$apartamento->numero,
            ])->values(),
        ])->values();

        return view('index', compact('residentes', 'residentesParaFormulario'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'residente_id' => ['required', 'exists:residentes,id'],
            'apartamento_id' => ['required', 'exists:apartamentos,id'],
            'empresa' => ['required', 'string', 'max:255'],
            'guia' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'detalles' => ['nullable', 'string'],
        ]);

        $residente = Residente::query()->findOrFail($datos['residente_id']);
        $apartamento = $residente->apartamentos()->where('activo', true)->find($datos['apartamento_id']);

        if (! $residente->activo || $apartamento === null) {
            return back()
                ->withInput()
                ->withErrors(['apartamento_id' => 'El apartamento seleccionado no pertenece al residente.']);
        }

        if ($request->hasFile('foto')) {
            $datos['foto'] = $request->file('foto')->store('paquetes', 'public');
        }

        $paquete = Paquete::query()->create([
            ...$datos,
            'estado' => 'Pendiente',
        ]);

        $notificacionEnviada = $this->notificarResidente($paquete);

        return redirect()
            ->route('confirmacion')
            ->with('notificacion_enviada', $notificacionEnviada);
    }

    public function index(): View
    {
        $paquetes = Paquete::query()
            ->with(['residente', 'apartamento', 'entregadoPor', 'autorizacionesRetiro'])
            ->latest()
            ->get();

        return view('paquetes', compact('paquetes'));
    }

    public function dashboard(): View
    {
        if (auth()->user()->rol === 'residente') {
            return app(ResidentePaqueteController::class)->dashboard(request());
        }

        $paquetes = Paquete::query()
            ->with(['residente', 'apartamento'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('paquetes'));
    }

    public function historial(Request $request): View
    {
        if ($request->user()->rol === 'residente') {
            return app(ResidentePaqueteController::class)->index($request);
        }

        $filtros = $request->validate([
            'apartamento' => ['nullable', 'string', 'max:255'],
            'residente' => ['nullable', 'string', 'max:255'],
            'busqueda' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:Todos,Pendiente,Entregado'],
            'fecha' => ['nullable', 'date'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $paquetes = Paquete::query()
            ->with(['residente', 'apartamento', 'entregadoPor', 'autorizacionesRetiro'])
            ->when($filtros['busqueda'] ?? null, function ($query, $busqueda) {
                $query->where(function ($subconsulta) use ($busqueda) {
                    $subconsulta
                        ->where('guia', 'like', "%{$busqueda}%")
                        ->orWhere('empresa', 'like', "%{$busqueda}%")
                        ->orWhereHas('residente', fn ($residente) => $residente->where('nombre', 'like', "%{$busqueda}%"))
                        ->orWhereHas('apartamento', function ($apartamento) use ($busqueda) {
                            $apartamento
                                ->where('numero', 'like', "%{$busqueda}%")
                                ->orWhere('torre', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->when($filtros['apartamento'] ?? null, function ($query, $apartamento) {
                $query->whereHas('apartamento', function ($apartamentoQuery) use ($apartamento) {
                    $apartamentoQuery
                        ->where('numero', 'like', "%{$apartamento}%")
                        ->orWhere('torre', 'like', "%{$apartamento}%");
                });
            })
            ->when(
                ($filtros['estado'] ?? null) && $filtros['estado'] !== 'Todos',
                fn ($query) => $query->where('estado', $filtros['estado'])
            )
            ->when($filtros['residente'] ?? null, fn ($query, $residente) => $query->whereHas('residente', fn ($subconsulta) => $subconsulta->where('nombre', 'like', "%{$residente}%")))
            ->when($filtros['fecha_desde'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filtros['fecha_hasta'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->when($filtros['fecha'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', $fecha))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('historial', compact('paquetes', 'filtros'));
    }

    public function entrega(?Paquete $paquete = null): View|RedirectResponse
    {
        $paquete ??= Paquete::query()
            ->where('estado', 'Pendiente')
            ->latest()
            ->firstOrFail();

        if ($paquete->estado !== 'Pendiente') {
            return redirect()
                ->route('historial')
                ->with('error', 'Este paquete ya fue entregado.');
        }

        $paquete->load(['residente', 'apartamento', 'autorizacionActiva']);

        return view('entrega', compact('paquete'));
    }

    public function consultar(Request $request): View
    {
        $busqueda = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:255'],
        ]);

        $paquetes = Paquete::query()
            ->with(['residente', 'apartamento', 'autorizacionActiva'])
            ->where('estado', 'Pendiente')
            ->when($busqueda['busqueda'] ?? null, function ($query, $terminoBusqueda) {
                // Dividir por comas y espacios para buscar múltiples términos
                $terminos = preg_split('/[\s,]+/', trim($terminoBusqueda), -1, PREG_SPLIT_NO_EMPTY);

                $query->where(function ($q) use ($terminos) {
                    foreach ($terminos as $termino) {
                        $q->orWhereHas('apartamento', function ($apt) use ($termino) {
                            $apt->where('numero', 'like', "%{$termino}%")
                                ->orWhere('torre', 'like', "%{$termino}%");
                        })
                            ->orWhereHas('residente', function ($res) use ($termino) {
                                $res->where('nombre', 'like', "%{$termino}%");
                            })
                            ->orWhere('guia', 'like', "%{$termino}%")
                            ->orWhere('id', 'like', "%{$termino}%");
                    }
                });
            })
            ->latest()
            ->get();

        return view('consultar', compact('paquetes', 'busqueda'));
    }

    public function confirmarEntrega(Request $request, Paquete $paquete): RedirectResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return redirect()
                ->route('historial')
                ->with('error', 'Este paquete ya fue entregado.');
        }

        $datos = $request->validate([
            'tipo_receptor' => ['required', 'in:residente,autorizado'],
            'firma' => [
                'required',
                'string',
                'max:2097152',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! $this->firmaTieneTrazo($value)) {
                        $fail('Debes registrar una firma válida antes de confirmar la entrega.');
                    }
                },
            ],
        ]);

        $entregado = DB::transaction(function () use ($paquete, $request, $datos): bool {
            $paqueteBloqueado = Paquete::query()
                ->with(['residente', 'autorizacionActiva'])
                ->lockForUpdate()
                ->find($paquete->id);

            if ($paqueteBloqueado === null || $paqueteBloqueado->estado !== 'Pendiente') {
                return false;
            }

            if ($paqueteBloqueado->residente === null) {
                throw ValidationException::withMessages([
                    'entrega' => 'No fue posible identificar al residente que recibe el paquete.',
                ]);
            }

            $autorizacion = $paqueteBloqueado->autorizacionActiva;
            $recibidoPor = $paqueteBloqueado->residente->nombre;

            if ($datos['tipo_receptor'] === 'autorizado') {
                if ($autorizacion === null) {
                    throw ValidationException::withMessages([
                        'tipo_receptor' => 'No existe una autorización activa para este paquete.',
                    ]);
                }

                $recibidoPor = $autorizacion->nombre_autorizado;
                $autorizacion->update(['estado' => AutorizacionRetiro::UTILIZADA]);
            } elseif ($autorizacion !== null) {
                $autorizacion->update(['estado' => AutorizacionRetiro::CANCELADA]);
            }

            $fechaEntrega = now();
            $paqueteBloqueado->update([
                'estado' => 'Entregado',
                'firma' => $datos['firma'],
                'entregado_por' => $request->user()->id,
                'fecha_entrega' => $fechaEntrega,
                'recibido_por' => $recibidoPor,
            ]);

            return true;
        });

        if (! $entregado) {
            return redirect()
                ->route('historial')
                ->with('error', 'Este paquete ya fue entregado.');
        }

        return redirect()
            ->route('historial')
            ->with('success', 'Paquete entregado correctamente.');
    }

    private function notificarResidente(Paquete $paquete): bool
    {
        $paquete->load(['residente.user', 'apartamento']);
        $usuario = $paquete->residente?->user;

        if ($usuario === null || blank($usuario->email)) {
            Log::info('Notificación de paquete omitida: residente sin usuario o correo.', [
                'paquete_id' => $paquete->id,
                'residente_id' => $paquete->residente_id,
            ]);

            return false;
        }

        try {
            Log::info('Intentando enviar notificación de nuevo paquete.', [
                'paquete_id' => $paquete->id,
                'residente_id' => $paquete->residente_id,
                'usuario_id' => $usuario->id,
            ]);

            $usuario->notify(new NuevoPaqueteNotification($paquete));

            Log::info('Notificación de nuevo paquete enviada.', [
                'paquete_id' => $paquete->id,
                'residente_id' => $paquete->residente_id,
                'usuario_id' => $usuario->id,
            ]);

            return true;
        } catch (\Throwable) {
            Log::warning('No fue posible enviar la notificación de nuevo paquete.', [
                'paquete_id' => $paquete->id,
                'residente_id' => $paquete->residente_id,
                'usuario_id' => $usuario->id,
            ]);

            return false;
        }
    }

    private function firmaTieneTrazo(string $firma): bool
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $firma, $coincidencias)) {
            return false;
        }

        $imagenBinaria = base64_decode($coincidencias[1], true);

        if ($imagenBinaria === false) {
            return false;
        }

        $dimensiones = @getimagesizefromstring($imagenBinaria);

        if ($dimensiones === false || $dimensiones[2] !== IMAGETYPE_PNG || $dimensiones[0] * $dimensiones[1] > 2_000_000) {
            return false;
        }

        $imagen = @imagecreatefromstring($imagenBinaria);

        if ($imagen === false) {
            return false;
        }

        for ($y = 0; $y < imagesy($imagen); $y++) {
            for ($x = 0; $x < imagesx($imagen); $x++) {
                if (((imagecolorat($imagen, $x, $y) >> 24) & 0x7F) < 127) {
                    imagedestroy($imagen);

                    return true;
                }
            }
        }

        imagedestroy($imagen);

        return false;
    }
}
