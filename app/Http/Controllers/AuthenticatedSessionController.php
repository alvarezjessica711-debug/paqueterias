<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        $user = $request->user();

        if (! $user->activo || ($user->rol === 'residente' && $user->residente !== null && ! $user->residente->activo)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.',
            ]);
        }

        $request->session()->regenerate();

        $rutaInicio = match ($user->rol) {
            'admin', 'vigilante' => 'dashboard',
            'residente' => 'dashboard-residente',
            default => 'login',
        };

        $request->session()->forget('url.intended');

        return redirect()->route($rutaInicio);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
