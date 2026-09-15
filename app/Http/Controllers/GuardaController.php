<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuardaController extends Controller
{
    public function index(): View
    {
        return view('guardas', ['guardas' => User::query()->where('rol', 'vigilante')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'rol' => 'vigilante', 'residente_id' => null, 'activo' => true]);

        return redirect()->route('guardas')->with('success', 'Vigilante registrado correctamente.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->rol === 'vigilante', 404);

        return view('guarda-editar', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->rol === 'vigilante', 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)], 'password' => ['nullable', 'string', 'min:8', 'confirmed']]);
        $user->update(array_filter(['name' => $data['name'], 'email' => $data['email'], 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null], fn ($v) => $v !== null));

        return redirect()->route('guardas')->with('success', 'Vigilante actualizado correctamente.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->rol === 'vigilante', 404);
        $user->update(['activo' => ! $user->activo]);

        return back()->with('success', $user->activo ? 'Vigilante reactivado correctamente.' : 'Vigilante desactivado correctamente.');
    }
}
