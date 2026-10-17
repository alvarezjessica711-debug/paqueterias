<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        abort_unless(is_string($plainToken) && $plainToken !== '', 401, 'Token requerido.');

        $tokenHash = hash('sha256', $plainToken);
        $userId = Cache::get('api-token:'.$tokenHash);
        $user = is_numeric($userId) ? User::query()->with('residente')->find($userId) : null;

        if ($user === null || ! $user->activo || ($user->rol === 'residente' && $user->residente !== null && ! $user->residente->activo)) {
            Cache::forget('api-token:'.$tokenHash);
            abort(401, 'Token inválido o cuenta inactiva.');
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
