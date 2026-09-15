<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ResidenteController extends Controller
{
    public function index(): View
    {
        $residentes = Residente::query()
            ->with(['apartamentos', 'user'])
            ->orderBy('nombre')
            ->get();

        $apartamentos = Apartamento::query()
            ->where('activo', true)
            ->orderBy('torre')
            ->orderBy('numero')
            ->get();

        return view('residentes', compact('residentes', 'apartamentos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255'],
            'apartamentos' => ['required', 'array', 'min:1'],
            'apartamentos.*' => ['integer', 'exists:apartamentos,id'],
            'crear_cuenta' => ['nullable', 'boolean'],
            'email_acceso' => ['nullable', 'required_if:crear_cuenta,1', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'required_if:crear_cuenta,1', 'string', 'min:8', 'confirmed'],
        ], [
            'nombre.required' => 'Debes ingresar el nombre del residente.',
            'correo.email' => 'El correo electronico no es valido.',
            'apartamentos.required' => 'Debes asociar por lo menos un apartamento.',
            'apartamentos.min' => 'Debes asociar por lo menos un apartamento.',
        ]);

        DB::transaction(function () use ($validated): void {
            $residente = Residente::query()->create(['nombre' => $validated['nombre'], 'telefono' => $validated['telefono'] ?? null, 'correo' => $validated['correo'] ?? null, 'activo' => true]);
            $residente->apartamentos()->sync($validated['apartamentos']);
            if (! empty($validated['crear_cuenta'])) {
                User::query()->create(['name' => $residente->nombre, 'email' => $validated['email_acceso'], 'password' => Hash::make($validated['password']), 'rol' => 'residente', 'residente_id' => $residente->id, 'activo' => true]);
            }
        });

        return redirect()
            ->route('residentes')
            ->with('success', 'Residente registrado correctamente.');
    }

    public function edit(Residente $residente): View
    {
        $residente->load(['apartamentos', 'user']);
        $apartamentos = Apartamento::query()->where('activo', true)->orWhereIn('id', $residente->apartamentos->pluck('id'))->orderBy('torre')->orderBy('numero')->get();

        return view('residente-editar', compact('residente', 'apartamentos'));
    }

    public function update(Request $request, Residente $residente): RedirectResponse
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:255'], 'telefono' => ['nullable', 'string', 'max:30'], 'correo' => ['nullable', 'email', 'max:255'], 'apartamentos' => ['required', 'array', 'min:1'], 'apartamentos.*' => ['integer', 'exists:apartamentos,id'], 'email_acceso' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($residente->user)], 'password' => ['nullable', 'string', 'min:8', 'confirmed']]);
        DB::transaction(function () use ($data, $residente) {
            $residente->update(['nombre' => $data['nombre'], 'telefono' => $data['telefono'] ?? null, 'correo' => $data['correo'] ?? null]);
            $residente->apartamentos()->sync($data['apartamentos']);
            if ($residente->user) {
                $u = ['name' => $residente->nombre];
                if (! empty($data['email_acceso'])) {
                    $u['email'] = $data['email_acceso'];
                }if (! empty($data['password'])) {
                    $u['password'] = Hash::make($data['password']);
                }$residente->user->update($u);
            }
        });

        return redirect()->route('residentes')->with('success', 'Residente actualizado correctamente.');
    }

    public function toggle(Residente $residente): RedirectResponse
    {
        $activo = ! $residente->activo;
        $residente->update(['activo' => $activo]);
        $residente->user?->update(['activo' => $activo]);

        return back()->with('success', $activo ? 'Residente reactivado correctamente.' : 'Residente desactivado correctamente.');
    }

    public function createAccount(Residente $residente): View
    {
        abort_if($residente->user()->exists(), 422, 'Este residente ya tiene una cuenta de acceso.');
        $residente->load('apartamentos');

        return view('residente-crear-cuenta', compact('residente'));
    }

    public function storeAccount(Request $request, Residente $residente): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($residente, $data): void {
            $residenteBloqueado = Residente::query()->lockForUpdate()->findOrFail($residente->id);
            abort_if($residenteBloqueado->user()->exists(), 422, 'Este residente ya tiene una cuenta de acceso.');

            User::query()->create([
                'name' => $residenteBloqueado->nombre,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'rol' => 'residente',
                'residente_id' => $residenteBloqueado->id,
                'activo' => $residenteBloqueado->activo,
            ]);
        });

        return redirect()->route('residentes')->with('success', 'Cuenta creada correctamente.');
    }
}
