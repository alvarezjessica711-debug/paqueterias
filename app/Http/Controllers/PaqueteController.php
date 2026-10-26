<?php

namespace App\Http\Controllers;

use App\Models\AutorizacionRetiro;
use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\PaqueteAuditoria;
use App\Models\Residente;
use App\Notifications\NuevoPaqueteNotification;
use App\Services\WhatsAppDeliveryCodeService;
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
        $casasParaFormulario = Apartamento::query()
            ->where('activo', true)
            ->with(['residentes' => fn ($query) => $query->where('activo', true)->orderBy('nombre')])
            ->orderBy('codigo')
            ->get()
            ->map(fn (Apartamento $casa) => [
                'id' => $casa->id,
                'codigo' => $casa->codigo,
                // No existe un marcador de residente principal. Solo una asociación única evita atribuir una identidad por suposición.
                'destinatario' => $casa->residentes->count() === 1 ? $casa->residentes->first()->nombre : '',
                'tieneResidenteUnico' => $casa->residentes->count() === 1,
            ])
            ->values();

        return view('index', compact('casasParaFormulario'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->exists('casa_id') || $request->exists('apartamento_id')) {
            $request->merge(['casa_id' => $request->input('casa_id', $request->input('apartamento_id'))]);
        }
        $datos = $request->validate([
            'casa_id' => ['required', 'exists:apartamentos,id'],
            'empresa' => ['required', 'string', 'max:255'],
            'destinatario' => ['nullable', 'string', 'max:255'],
            'guia' => ['nullable', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'detalles' => ['nullable', 'string'],
        ], [], ['casa_id' => 'código de casa']);

        $casa = Apartamento::query()
            ->where('activo', true)
            ->with(['residentes' => fn ($query) => $query->where('activo', true)->orderBy('nombre')])
            ->find($datos['casa_id']);

        if ($casa === null) {
            return back()
                ->withInput()
                ->withErrors(['casa_id' => 'Selecciona una casa activa registrada.']);
        }

        $residente = $casa->residentes->count() === 1 ? $casa->residentes->first() : null;
        $destinatarioIngresado = trim((string) ($datos['destinatario'] ?? ''));
        $datos['destinatario'] = $destinatarioIngresado !== '' ? $destinatarioIngresado : $residente?->nombre;
        if (blank($datos['destinatario'])) {
            throw ValidationException::withMessages(['destinatario' => 'Escribe el nombre de la persona que recibirá el paquete.']);
        }
        $datos['residente_id'] = $residente?->id;
        // La columna se conserva por compatibilidad con registros, búsquedas y reportes existentes.
        $datos['direccion'] = $casa->codigo;
        unset($datos['casa_id']);
        $datos['apartamento_id'] = $casa->id;

        if ($request->hasFile('foto')) {
            $datos['foto'] = $request->file('foto')->store('paquetes', 'public');
        }

        $paquete = DB::transaction(function () use ($datos, $request): Paquete {
            $paquete = Paquete::query()->create([...$datos, 'estado' => 'Pendiente']);
            $this->registrarAuditoria($paquete, $request->user()->id, 'registrado', [
                'destinatario' => [null, $paquete->destinatario],
                'direccion' => [null, $paquete->direccion],
                'estado' => [null, $paquete->estado],
            ]);

            return $paquete;
        });

        $notificacionEnviada = $this->notificarResidente($paquete);
        $estadoWhatsApp = app(WhatsAppDeliveryCodeService::class)->send($paquete);

        return redirect()
            ->route('confirmacion')
            ->with('notificacion_enviada', $notificacionEnviada)
            ->with('whatsapp_estado', $estadoWhatsApp)
            ->with('codigo_entrega', $estadoWhatsApp === 'accepted' ? null : $paquete->codigo_entrega);
    }

    public function index(): View
    {
        $paquetes = Paquete::query()
            ->with(['residente', 'casa', 'entregadoPor', 'autorizacionesRetiro'])
            ->latest()
            ->get();

        return view('paquetes', compact('paquetes'));
    }

    public function edit(Paquete $paquete): View
    {
        abort_unless($paquete->estado === 'Pendiente', 409, 'Solo se pueden corregir paquetes pendientes.');
        $paquete->load(['residente', 'casa']);

        return view('paquete-editar', compact('paquete'));
    }

    public function update(Request $request, Paquete $paquete): RedirectResponse
    {
        abort_unless($paquete->estado === 'Pendiente', 409, 'Solo se pueden corregir paquetes pendientes.');
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'guia' => ['required', 'string', 'max:100'],
            'destinatario' => ['required', 'string', 'max:255'],
            'detalles' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($paquete, $data, $request): void {
            $antes = $paquete->only(array_keys($data));
            $paquete->update($data);
            $cambios = [];
            foreach ($data as $campo => $valor) {
                if (($antes[$campo] ?? null) != $valor) {
                    $cambios[$campo] = [$antes[$campo] ?? null, $valor];
                }
            }
            $this->registrarAuditoria($paquete, $request->user()->id, 'modificado', $cambios);
        });

        return redirect()->route('historial')->with('success', 'Paquete actualizado correctamente.');
    }

    public function dashboard(): View
    {
        if (auth()->user()->rol === 'residente') {
            return app(ResidentePaqueteController::class)->dashboard(request());
        }

        $paquetes = Paquete::query()
            ->with(['residente', 'casa'])
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
            'casa' => ['nullable', 'string', 'max:255'],
            'residente' => ['nullable', 'string', 'max:255'],
            'busqueda' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:Todos,Pendiente,Entregado'],
            'fecha' => ['nullable', 'date'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);
        $casa = $filtros['casa'] ?? null;

        $paquetes = Paquete::query()
            ->with(['residente', 'casa', 'entregadoPor', 'autorizacionesRetiro'])
            ->when($filtros['busqueda'] ?? null, function ($query, $busqueda) {
                $query->where(function ($subconsulta) use ($busqueda) {
                    $subconsulta
                        ->where('guia', 'like', "%{$busqueda}%")
                        ->orWhere('empresa', 'like', "%{$busqueda}%")
                        ->orWhere('destinatario', 'like', "%{$busqueda}%")
                        ->orWhereHas('residente', fn ($residente) => $residente->where('nombre', 'like', "%{$busqueda}%"))
                        ->orWhereHas('casa', fn ($casaQuery) => $casaQuery->where('codigo', 'like', "%{$busqueda}%"));
                });
            })
            ->when($casa, function ($query, $casa) {
                $query->whereHas('casa', fn ($casaQuery) => $casaQuery->where('codigo', 'like', "%{$casa}%"));
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

        $auditorias = in_array($request->user()->rol, ['admin', 'superadmin'], true)
            ? PaqueteAuditoria::query()->with(['paquete', 'usuario'])->latest('created_at')->paginate(25, ['*'], 'auditoria_page')
            : collect();

        return view('historial', compact('paquetes', 'filtros', 'auditorias'));
    }

    public function auditoria(Request $request, Paquete $paquete): View
    {
        abort_unless(in_array($request->user()->rol, ['admin', 'vigilante', 'superadmin'], true), 403);
        $auditorias = $paquete->auditorias()->with('usuario')->get();

        return view('paquete-auditoria', compact('paquete', 'auditorias'));
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

        // Legacy pending packages predate delivery codes; issue one lazily without changing other package data.
        if (blank($paquete->codigo_entrega)) {
            $paquete->forceFill(['codigo_entrega' => Paquete::generarCodigoUnico()])->save();
        }

        $paquete->load(['residente', 'casa', 'autorizacionActiva']);
        $estadoWhatsApp = null;
        if ($paquete->codigo_entrega_whatsapp_aceptado_at === null) {
            $estadoWhatsApp = app(WhatsAppDeliveryCodeService::class)->send($paquete);
            $paquete->refresh();
        }
        $codigoManual = $paquete->codigo_entrega_whatsapp_aceptado_at === null ? $paquete->codigo_entrega : null;
        $codigoValidado = $paquete->codigo_entrega_validado_por === request()->user()->id
            && $paquete->codigo_entrega_validado_at?->greaterThan(now()->subMinutes(5));

        return view('entrega', compact('paquete', 'codigoValidado', 'codigoManual', 'estadoWhatsApp'));
    }

    public function consultar(Request $request): View
    {
        $busqueda = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:255'],
        ]);
        $termino = trim($busqueda['busqueda'] ?? '');

        $paquetes = Paquete::query()
            ->with(['residente', 'casa', 'autorizacionActiva'])
            ->where('estado', 'Pendiente')
            ->when($termino !== '', function ($query) use ($termino): void {
                $query->where(function ($coincidencias) use ($termino): void {
                    $coincidencias
                        ->orWhere('destinatario', 'like', "%{$termino}%")
                        ->orWhereHas('casa', fn ($casa) => $casa->where('codigo', strtoupper($termino)))
                        ->orWhereHas('residente', fn ($residente) => $residente->where('nombre', 'like', "%{$termino}%"))
                        ->orWhere('guia', 'like', "%{$termino}%");
                });
            })
            ->latest()
            ->get();

        return view('consultar', compact('paquetes', 'busqueda'));
    }

    public function validarCodigoEntrega(Request $request, Paquete $paquete): RedirectResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return redirect()->route('historial')->with('error', 'Este paquete ya fue entregado.');
        }

        $datos = $request->validate([
            'codigo_entrega' => ['required', 'string', 'max:10'],
        ]);

        $valido = DB::transaction(function () use ($paquete, $request, $datos): bool {
            $paqueteBloqueado = Paquete::query()
                ->with(['residente', 'casa'])
                ->lockForUpdate()
                ->find($paquete->id);

            if ($paqueteBloqueado === null || $paqueteBloqueado->estado !== 'Pendiente' || $paqueteBloqueado->codigo_entrega_usado_en !== null) {
                return false;
            }

            $destinatario = trim((string) ($paqueteBloqueado->destinatario ?: $paqueteBloqueado->residente?->nombre));
            if ($paqueteBloqueado->casa === null || $destinatario === '') {
                return false;
            }

            $coincide = hash_equals(
                (string) $paqueteBloqueado->codigo_entrega,
                strtoupper(trim($datos['codigo_entrega'])),
            );

            if (! $coincide) {
                $paqueteBloqueado->forceFill([
                    'codigo_entrega_validado_por' => null,
                    'codigo_entrega_validado_at' => null,
                ])->save();

                return false;
            }

            $paqueteBloqueado->forceFill([
                'codigo_entrega_validado_por' => $request->user()->id,
                'codigo_entrega_validado_at' => now(),
            ])->save();

            return true;
        });

        if (! $valido) {
            if ($paquete->fresh()->estado !== 'Pendiente') {
                return redirect()->route('historial')->with('error', 'Este paquete ya fue entregado.');
            }

            return back()->withErrors([
                'codigo_entrega' => 'El código no es válido para este paquete, o falta confirmar la casa y el destinatario registrados.',
            ])->withInput();
        }

        return back()->with('codigo_validado', true)->with('success', 'Código válido. Confirma la entrega para continuar.');
    }

    public function confirmarEntrega(Request $request, Paquete $paquete): RedirectResponse
    {
        if ($paquete->estado !== 'Pendiente') {
            return redirect()->route('historial')->with('error', 'Este paquete ya fue entregado.');
        }

        $datos = $request->validate([
            'tipo_receptor' => ['required', 'in:residente,autorizado'],
        ]);

        $entregado = DB::transaction(function () use ($paquete, $request, $datos): bool {
            $paqueteBloqueado = Paquete::query()
                ->with(['residente', 'casa', 'autorizacionActiva'])
                ->lockForUpdate()
                ->find($paquete->id);

            if ($paqueteBloqueado === null || $paqueteBloqueado->estado !== 'Pendiente') {
                return false;
            }

            $validacionReciente = $paqueteBloqueado->codigo_entrega_validado_por === $request->user()->id
                && $paqueteBloqueado->codigo_entrega_validado_at?->greaterThan(now()->subMinutes(5));

            if (! $validacionReciente || $paqueteBloqueado->codigo_entrega_usado_en !== null || blank($paqueteBloqueado->codigo_entrega)) {
                return false;
            }

            $destinatario = trim((string) ($paqueteBloqueado->destinatario ?: $paqueteBloqueado->residente?->nombre));
            if ($paqueteBloqueado->casa === null || $destinatario === '') {
                return false;
            }

            $autorizacion = $paqueteBloqueado->autorizacionActiva;
            $recibidoPor = $destinatario;

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
                'entregado_por' => $request->user()->id,
                'fecha_entrega' => $fechaEntrega,
                'recibido_por' => $recibidoPor,
                'codigo_entrega_validado_por' => null,
                'codigo_entrega_validado_at' => null,
                'codigo_entrega_usado_en' => $fechaEntrega,
            ]);
            $this->registrarAuditoria($paqueteBloqueado, $request->user()->id, 'entregado', [
                'estado' => ['Pendiente', 'Entregado'],
                'entregado_por' => [null, $request->user()->id],
                'fecha_entrega' => [null, $fechaEntrega->toISOString()],
                'recibido_por' => [null, $recibidoPor],
                'codigo_entrega_usado_en' => [null, $fechaEntrega->toISOString()],
            ]);

            return true;
        });

        if (! $entregado) {
            if ($paquete->fresh()->estado !== 'Pendiente') {
                return redirect()->route('historial')->with('error', 'Este paquete ya fue entregado.');
            }

            return back()->withErrors([
                'codigo_entrega' => 'Valida primero el código de este paquete. La validación vence después de cinco minutos.',
            ]);
        }

        return redirect()->route('historial')->with('success', 'Paquete entregado correctamente.');
    }

    private function notificarResidente(Paquete $paquete): bool
    {
        $paquete->load(['residente.user', 'casa']);
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


}
