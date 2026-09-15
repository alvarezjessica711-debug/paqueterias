<?php

namespace App\Http\Controllers;

use App\Models\Paquete;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $this->filtros($request);
        $consulta = $this->consultaFiltrada($filtros);

        $total = (clone $consulta)->count();
        $pendientes = (clone $consulta)->where('estado', 'Pendiente')->count();
        $entregados = (clone $consulta)->where('estado', 'Entregado')->count();
        $paquetesEntregados = (clone $consulta)->where('estado', 'Entregado')->whereNotNull('fecha_entrega')->get(['id', 'created_at', 'fecha_entrega']);
        $promedioHoras = $paquetesEntregados->isEmpty() ? null : $paquetesEntregados->avg(fn (Paquete $paquete) => $paquete->created_at->diffInMinutes($paquete->fecha_entrega) / 60);
        $enCustodia = (clone $consulta)->where('estado', 'Pendiente')->where('created_at', '<', now()->subDays($filtros['dias_custodia']))->count();
        $paquetes = $consulta->latest('created_at')->paginate(15)->withQueryString();

        return view('reportes', compact('paquetes', 'filtros', 'total', 'pendientes', 'entregados', 'promedioHoras', 'enCustodia'));
    }

    public function exportar(Request $request): StreamedResponse
    {
        $filtros = $this->filtros($request);
        $fechaDesde = $filtros['fecha_desde'] ?? now()->format('Y-m-d');
        $fechaHasta = $filtros['fecha_hasta'] ?? now()->format('Y-m-d');

        return response()->streamDownload(function () use ($filtros): void {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, ['Fecha recepción', 'Guía', 'Empresa', 'Residente', 'Torre', 'Apartamento', 'Estado', 'Fecha entrega', 'Recibido por', 'Guarda', 'Tiempo de retiro', 'Días en custodia'], ';');

            $this->consultaFiltrada($filtros)->latest('created_at')->get()->each(function (Paquete $paquete) use ($archivo): void {
                fputcsv($archivo, [
                    $paquete->created_at?->format('d/m/Y H:i') ?? '-', $paquete->guia ?? '-', $paquete->empresa ?? '-',
                    $paquete->residente?->nombre ?? '-', $paquete->apartamento?->torre ?? '-', $paquete->apartamento?->numero ?? '-',
                    $paquete->estado, $paquete->fecha_entrega?->format('d/m/Y H:i') ?? '-', $paquete->recibido_por ?? '-',
                    $paquete->entregadoPor?->name ?? '-', $this->tiempoRetiro($paquete), $this->diasCustodia($paquete),
                ], ';');
            });

            fclose($archivo);
        }, "reporte-paquetes-{$fechaDesde}-a-{$fechaHasta}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function filtros(Request $request): array
    {
        $filtros = $request->validate([
            'fecha_desde' => ['nullable', 'date'], 'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'estado' => ['nullable', 'in:Todos,Pendiente,Entregado'], 'torre' => ['nullable', 'string', 'max:100'],
            'apartamento' => ['nullable', 'string', 'max:100'], 'residente' => ['nullable', 'string', 'max:255'],
            'dias_custodia' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $filtros['estado'] ??= 'Todos';
        $filtros['dias_custodia'] ??= 3;

        return $filtros;
    }

    /** @param array<string, mixed> $filtros */
    private function consultaFiltrada(array $filtros): Builder
    {
        return Paquete::query()->with(['residente', 'apartamento', 'entregadoPor'])
            ->when($filtros['fecha_desde'] ?? null, fn (Builder $query, string $fecha) => $query->whereDate('created_at', '>=', $fecha))
            ->when($filtros['fecha_hasta'] ?? null, fn (Builder $query, string $fecha) => $query->whereDate('created_at', '<=', $fecha))
            ->when(($filtros['estado'] ?? 'Todos') !== 'Todos', fn (Builder $query) => $query->where('estado', $filtros['estado']))
            ->when($filtros['torre'] ?? null, fn (Builder $query, string $torre) => $query->whereHas('apartamento', fn (Builder $apartamento) => $apartamento->where('torre', 'like', "%{$torre}%")))
            ->when($filtros['apartamento'] ?? null, fn (Builder $query, string $numero) => $query->whereHas('apartamento', fn (Builder $apartamento) => $apartamento->where('numero', 'like', "%{$numero}%")))
            ->when($filtros['residente'] ?? null, fn (Builder $query, string $residente) => $query->whereHas('residente', fn (Builder $queryResidente) => $queryResidente->where('nombre', 'like', "%{$residente}%")));
    }

    private function tiempoRetiro(Paquete $paquete): string
    {
        if ($paquete->estado !== 'Entregado' || ! $paquete->fecha_entrega) {
            return 'Pendiente';
        }

        return number_format($paquete->created_at->diffInMinutes($paquete->fecha_entrega) / 60, 1, ',', '.').' horas';
    }

    private function diasCustodia(Paquete $paquete): string
    {
        return $paquete->estado === 'Pendiente' ? (string) $paquete->created_at->diffInDays(now()) : '-';
    }
}
