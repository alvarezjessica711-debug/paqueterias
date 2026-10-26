<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\PaqueteAuditoria;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApartamentoController extends Controller
{
    public function index(): View
    {
        return view('apartamentos', ['casas' => Apartamento::query()->with('residentes')->orderBy('codigo')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'regex:/^[A-F][1-9][0-9]*$/', Rule::unique('apartamentos', 'codigo')],
        ], [], ['codigo' => 'código de casa']);
        Apartamento::query()->create([...$data, 'activo' => true]);

        return back()->with('success', 'Casa creada correctamente.');
    }

    public function update(Request $request, Apartamento $apartamento): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'regex:/^[A-F][1-9][0-9]*$/', Rule::unique('apartamentos', 'codigo')->ignore($apartamento)],
        ], [], ['codigo' => 'código de casa']);

        DB::transaction(function () use ($apartamento, $data, $request): void {
            $codigoAnterior = $apartamento->codigo;
            $apartamento->update($data);

            if ($codigoAnterior !== $apartamento->codigo) {
                foreach ($apartamento->paquetes()->pluck('id') as $paqueteId) {
                    PaqueteAuditoria::query()->create([
                        'paquete_id' => $paqueteId,
                        'user_id' => $request->user()->id,
                        'accion' => 'modificado',
                        'campo' => 'casa',
                        'valor_anterior' => $codigoAnterior,
                        'valor_nuevo' => $apartamento->codigo,
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Casa actualizada correctamente.');
    }

    public function toggle(Apartamento $apartamento): RedirectResponse
    {
        $apartamento->update(['activo' => ! $apartamento->activo]);

        return back()->with('success', 'Estado de la casa actualizado.');
    }
}
