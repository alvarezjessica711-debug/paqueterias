<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConfiguracionController extends Controller
{
    public function index(): View
    {
        $perfil = session('configuracion.perfil', [
            'nombre' => auth()->user()?->name ?? 'Carlos Mendoza',
            'turno' => 'Tarde (2:00 PM - 10:00 PM)',
        ]);

        $mensaje = session('configuracion.mensaje', [
            'plantilla' => '¡Hola! Tienes un nuevo paquete esperando por ti en la portería del Conjunto Valle de San Remo. Puedes pasar a recogerlo en nuestro horario de atención.',
            'activar_wpp' => true,
        ]);

        return view('configuracion', compact('perfil', 'mensaje'));
    }

    public function actualizarPerfil(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'turno' => ['required', 'string', 'max:255'],
        ]);

        if ($user = auth()->user()) {
            $user->update(['name' => $datos['nombre']]);
        }

        session(['configuracion.perfil' => $datos]);

        return redirect()->route('configuracion')->with('success_perfil', 'Perfil actualizado correctamente.');
    }

    public function actualizarMensaje(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'plantilla' => ['required', 'string'],
            'activar_wpp' => ['nullable', 'accepted'],
        ]);

        session(['configuracion.mensaje' => [
            'plantilla' => $datos['plantilla'],
            'activar_wpp' => $request->boolean('activar_wpp'),
        ]]);

        return redirect()->route('configuracion')->with('success_mensaje', 'Mensaje de WhatsApp actualizado correctamente.');
    }

    public function actualizarPassword(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'password_actual' => ['required', 'string'],
            'password_nueva' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! $user || ! Hash::check($datos['password_actual'], $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => Hash::make($datos['password_nueva'])]);

        return redirect()->route('configuracion')->with('success_password', 'Contraseña actualizada correctamente.');
    }
}
