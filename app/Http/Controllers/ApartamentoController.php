<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApartamentoController extends Controller
{
    public function index(): View
    {
        return view('apartamentos', ['apartamentos' => Apartamento::query()->with('residentes')->orderBy('torre')->orderBy('numero')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['torre' => ['required', 'string', 'max:100'], 'numero' => ['required', 'string', 'max:100', Rule::unique('apartamentos', 'numero')->where(fn ($q) => $q->where('torre', $request->input('torre')))]]);
        Apartamento::query()->create([...$data, 'activo' => true]);

        return back()->with('success', 'Apartamento creado correctamente.');
    }

    public function update(Request $request, Apartamento $apartamento): RedirectResponse
    {
        $data = $request->validate(['torre' => ['required', 'string', 'max:100'], 'numero' => ['required', 'string', 'max:100', Rule::unique('apartamentos', 'numero')->where(fn ($q) => $q->where('torre', $request->input('torre')))->ignore($apartamento)]]);
        $apartamento->update($data);

        return back()->with('success', 'Apartamento actualizado correctamente.');
    }

    public function toggle(Apartamento $apartamento): RedirectResponse
    {
        $apartamento->update(['activo' => ! $apartamento->activo]);

        return back()->with('success', 'Estado del apartamento actualizado.');
    }
}
