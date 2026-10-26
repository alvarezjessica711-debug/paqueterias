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
            ->with(['casas', 'user'])
            ->orderBy('nombre')
            ->get();

        $casas = Apartamento::query()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return view('residentes', compact('residentes', 'casas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['casas' => $request->input('casas', $request->input('apartamentos'))]);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255'],
            'casas' => ['required', 'array', 'min:1'],
            'casas.*' => ['integer', 'exists:apartamentos,id'],
        ], [
            'nombre.required' => 'Debes ingresar el nombre del residente.',
            'correo.email' => 'El correo electronico no es valido.',
            'casas.required' => 'Debes asociar por lo menos una casa.',
            'casas.min' => 'Debes asociar por lo menos una casa.',
            'casas.*.exists' => 'La casa seleccionada no existe.',
            'casas.*.integer' => 'La casa seleccionada no es válida.',
        ]);

        DB::transaction(function () use ($validated): void {
            $residente = Residente::query()->create(['nombre' => $validated['nombre'], 'telefono' => $validated['telefono'] ?? null, 'correo' => $validated['correo'] ?? null, 'activo' => true]);
            $residente->casas()->sync($validated['casas']);
        });

        return redirect()
            ->route('residentes')
            ->with('success', 'Residente registrado correctamente.');
    }

    public function edit(Residente $residente): View
    {
        $residente->load(['casas', 'user']);
        $casas = Apartamento::query()->where('activo', true)->orWhereIn('id', $residente->casas->pluck('id'))->orderBy('codigo')->get();

        return view('residente-editar', compact('residente', 'casas'));
    }

    public function update(Request $request, Residente $residente): RedirectResponse
    {
        $request->merge(['casas' => $request->input('casas', $request->input('apartamentos'))]);
        $data = $request->validate(['nombre' => ['required', 'string', 'max:255'], 'telefono' => ['nullable', 'string', 'max:30'], 'correo' => ['nullable', 'email', 'max:255'], 'casas' => ['required', 'array', 'min:1'], 'casas.*' => ['integer', 'exists:apartamentos,id'], 'email_acceso' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($residente->user)], 'password' => ['nullable', 'string', 'min:8', 'confirmed']]);
        DB::transaction(function () use ($data, $residente) {
            $residente->update(['nombre' => $data['nombre'], 'telefono' => $data['telefono'] ?? null, 'correo' => $data['correo'] ?? null]);
            $residente->casas()->sync($data['casas']);
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
        $residente->load('casas');

        return view('residente-crear-cuenta', compact('residente'));
    }

    public function storeAccount(Request $request, Residente $residente): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
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

        return redirect()->route('usuarios')->with('success', 'Cuenta creada correctamente.');
    }
}
