<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticationController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->with('residente')->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Credenciales incorrectas.', 401);
        }

        if (! $user->activo || ($user->rol === 'residente' && $user->residente !== null && ! $user->residente->activo)) {
            return $this->error('La cuenta se encuentra inactiva.', 403);
        }

        $token = Str::random(64);
        Cache::put('api-token:'.hash('sha256', $token), $user->id, now()->addHours(12));

        return response()->json([
            'data' => ['token' => $token, 'tipo' => 'Bearer', 'expira_en_segundos' => 43200],
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'rol' => $user->rol],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        Cache::forget('api-token:'.hash('sha256', (string) $token));

        return response()->json(['message' => 'Sesión API cerrada.']);
    }
}
