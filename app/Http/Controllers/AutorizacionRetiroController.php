<?php

namespace App\Http\Controllers;

use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutorizacionRetiroController extends Controller
{
    public function create(Request $request, Paquete $paquete): View
    {
        $this->validarPaqueteDelResidente($request, $paquete);
        abort_unless($paquete->estado === 'Pendiente', 404);

        return view('autorizacion-retiro', compact('paquete'));
    }

    public function store(Request $request, Paquete $paquete): RedirectResponse
    {
        $this->validarPaqueteDelResidente($request, $paquete);

        $datos = $request->validate([
            'nombre_autorizado' => ['required', 'string', 'max:255'],
            'documento_autorizado' => ['required', 'string', 'max:100'],
            'relacion' => ['required', 'string', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($paquete, $request, $datos): void {
            $paqueteBloqueado = Paquete::query()->lockForUpdate()->findOrFail($paquete->id);

            if ($paqueteBloqueado->estado !== 'Pendiente') {
                throw ValidationException::withMessages([
                    'autorizacion' => 'Solo puedes autorizar el retiro de paquetes pendientes.',
                ]);
            }

            $existeAutorizacionActiva = AutorizacionRetiro::query()
                ->where('paquete_id', $paqueteBloqueado->id)
                ->where('estado', AutorizacionRetiro::ACTIVA)
                ->exists();

            if ($existeAutorizacionActiva) {
                throw ValidationException::withMessages([
                    'autorizacion' => 'Este paquete ya tiene una autorización activa.',
                ]);
            }

            AutorizacionRetiro::query()->create([
                ...$datos,
                'paquete_id' => $paqueteBloqueado->id,
                'residente_id' => $request->user()->residente_id,
                'estado' => AutorizacionRetiro::ACTIVA,
            ]);
        });

        return redirect()
            ->route('mis-paquetes')
            ->with('success', 'Persona autorizada correctamente para retirar este paquete.');
    }

    public function cancelar(Request $request, Paquete $paquete, AutorizacionRetiro $autorizacion): RedirectResponse
    {
        $this->validarPaqueteDelResidente($request, $paquete);
        abort_unless($autorizacion->paquete_id === $paquete->id && $autorizacion->residente_id === $request->user()->residente_id, 403);

        DB::transaction(function () use ($paquete, $autorizacion): void {
            $paqueteBloqueado = Paquete::query()->lockForUpdate()->findOrFail($paquete->id);
            $autorizacionBloqueada = AutorizacionRetiro::query()->lockForUpdate()->findOrFail($autorizacion->id);

            if ($paqueteBloqueado->estado !== 'Pendiente' || $autorizacionBloqueada->estado !== AutorizacionRetiro::ACTIVA) {
                throw ValidationException::withMessages([
                    'autorizacion' => 'Esta autorización ya no puede cancelarse.',
                ]);
            }

            $autorizacionBloqueada->update(['estado' => AutorizacionRetiro::CANCELADA]);
        });

        return redirect()
            ->route('mis-paquetes')
            ->with('success', 'Autorización cancelada correctamente.');
    }

    private function validarPaqueteDelResidente(Request $request, Paquete $paquete): void
    {
        abort_unless(
            $request->user()->residente_id !== null && $paquete->residente_id === $request->user()->residente_id,
            403,
        );
    }
}
