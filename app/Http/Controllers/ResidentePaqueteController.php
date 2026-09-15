<?php

namespace App\Http\Controllers;

use App\Models\Paquete;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ResidentePaqueteController extends Controller
{
    public function dashboard(Request $request): View
    {
        $residenteId = $request->user()->residente_id;
        abort_if($residenteId === null, 403);

        $residente = $request->user()->residente;

        $inicioMes = now()->startOfMonth();
        $paquetesMes = Paquete::query()
            ->with('apartamento')
            ->where('residente_id', $residenteId)
            ->where('created_at', '>=', $inicioMes)
            ->latest()
            ->get();

        return view('dashboard-residente', [
            'paquetes' => $paquetesMes->take(5),
            'residenteNombre' => $residente->nombre,
            'pendientes' => $paquetesMes->where('estado', 'Pendiente')->count(),
            'entregados' => $paquetesMes->where('estado', 'Entregado')->count(),
            'totalMes' => $paquetesMes->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $residenteId = $request->user()->residente_id;
        abort_if($residenteId === null, 403);

        $filtros = $request->validate([
            'estado' => ['nullable', 'in:Todos,Pendiente,Entregado'],
            'fecha' => ['nullable', 'date'],
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $paquetes = $this->paquetesDelResidente($residenteId, $filtros)
            ->paginate(15)
            ->withQueryString();

        return view('mis-paquetes', compact('paquetes', 'filtros'));
    }

    private function paquetesDelResidente(int $residenteId, array $filtros)
    {
        return Paquete::query()
            ->with(['apartamento', 'autorizacionActiva'])
            ->where('residente_id', $residenteId)
            ->when(
                ($filtros['estado'] ?? null) && $filtros['estado'] !== 'Todos',
                fn ($query) => $query->where('estado', $filtros['estado'])
            )
            ->when($filtros['fecha'] ?? null, fn ($query, $fecha) => $query->whereDate('created_at', $fecha))
            ->when($filtros['mes'] ?? null, function ($query, $mes) {
                $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
                $query->whereBetween('created_at', [$inicio, $inicio->copy()->endOfMonth()]);
            }, function ($query) {
                $query->where('created_at', '>=', now()->startOfMonth());
            })
            ->latest();
    }
}
