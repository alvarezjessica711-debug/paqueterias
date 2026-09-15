<?php

namespace App\Http\Controllers;

use App\Models\Mensajeria;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MensajeriaController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:255'],
        ]);

        $mensajerias = Mensajeria::query()
            ->when($busqueda['busqueda'] ?? null, function ($query, $termino) {
                $query->where(function ($q) use ($termino) {
                    $q->where('nombre', 'like', "%{$termino}%")
                        ->orWhere('tipo_servicio', 'like', "%{$termino}%")
                        ->orWhere('linea_atencion', 'like', "%{$termino}%")
                        ->orWhere('email', 'like', "%{$termino}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('mensajeria', compact('mensajerias', 'busqueda'));
    }

    public function create(): View
    {
        return view('mensajeria.crear');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:mensajerias'],
            'tipo_servicio' => ['nullable', 'string', 'max:255'],
            'linea_atencion' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
        ]);

        Mensajeria::query()->create($datos);

        return redirect()->route('mensajeria')->with('mensaje', 'Mensajería agregada correctamente.');
    }

    public function edit(Mensajeria $mensajeria): View
    {
        return view('mensajeria.editar', compact('mensajeria'));
    }

    public function update(Request $request, Mensajeria $mensajeria): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:mensajerias,nombre,'.$mensajeria->id],
            'tipo_servicio' => ['nullable', 'string', 'max:255'],
            'linea_atencion' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
        ]);

        $mensajeria->update($datos);

        return redirect()->route('mensajeria')->with('mensaje', 'Mensajería actualizada correctamente.');
    }

    public function destroy(Mensajeria $mensajeria): RedirectResponse
    {
        $mensajeria->delete();

        return redirect()->route('mensajeria')->with('mensaje', 'Mensajería eliminada correctamente.');
    }
}
