<?php

namespace App\Http\Controllers;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('usuarios', [
            'users' => User::query()->with('residente')->orderBy('name')->get(),
            'residentes' => Residente::query()->where('activo', true)->whereDoesntHave('user')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        DB::transaction(fn () => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol' => $data['rol'],
            'residente_id' => $data['residente_id'] ?? null,
            'activo' => true,
        ]));

        return redirect()->route('usuarios')->with('success', 'Usuario creado correctamente.');
    }
}
