<?php

namespace App\Http\Controllers\Api;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementApiController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'rol' => ['required', Rule::in(['admin', 'vigilante', 'residente', 'superadmin'])],
            'residente_id' => ['required_if:rol,residente', 'nullable', 'integer', 'exists:residentes,id', Rule::unique('users', 'residente_id')],
        ]);

        if ($data['rol'] === 'residente') {
            $residente = Residente::query()->findOrFail($data['residente_id']);
            if (! $residente->activo) {
                throw ValidationException::withMessages(['residente_id' => 'No se puede crear una cuenta para un residente inactivo.']);
            }
        } else {
            $data['residente_id'] = null;
        }

        $user = DB::transaction(fn () => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol' => $data['rol'],
            'residente_id' => $data['residente_id'] ?? null,
            'activo' => true,
        ]));

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'rol' => $user->rol,
            'residente_id' => $user->residente_id,
            'activo' => $user->activo,
        ]], 201);
    }
}
