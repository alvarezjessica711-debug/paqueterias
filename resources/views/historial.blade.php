<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Entregas - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Historial de Entregas</h2><p>Consulta el historial de paquetes registrados.</p></div>
            <div class="contenedor-tabla">
                @if (session('success'))
                    <div class="alerta-exito">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alerta-error">{{ session('error') }}</div>
                @endif

                <form class="filtros-historial" method="get" action="{{ route('historial') }}">
                    <div class="filtro-grupo" style="flex: 2;">
                        <label for="busqueda">Buscar</label>
                        <div class="filtro-input">
                            <i class="fas fa-search"></i>
                            <input
                                type="text"
                                id="busqueda"
                                name="busqueda"
                                value="{{ $filtros['busqueda'] ?? '' }}"
                                placeholder="Guía, residente, apartamento o empresa"
                            >
                        </div>
                    </div>
                    <div class="filtro-grupo"><label for="residente">Residente</label><input type="text" id="residente" name="residente" class="filtro-fecha" value="{{ $filtros['residente'] ?? '' }}" placeholder="Nombre del residente"></div>
                    <div class="filtro-grupo"><label for="apartamento">Apartamento</label><input type="text" id="apartamento" name="apartamento" class="filtro-fecha" value="{{ $filtros['apartamento'] ?? '' }}" placeholder="Torre o número"></div>
                    <div class="filtro-grupo">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="filtro-select">
                            <option value="Todos" @selected(($filtros['estado'] ?? 'Todos') === 'Todos')>Todos</option>
                            <option value="Pendiente" @selected(($filtros['estado'] ?? null) === 'Pendiente')>Pendiente</option>
                            <option value="Entregado" @selected(($filtros['estado'] ?? null) === 'Entregado')>Entregado</option>
                        </select>
                    </div>
                    <div class="filtro-grupo"><label for="fecha_desde">Fecha desde</label><input type="date" id="fecha_desde" name="fecha_desde" class="filtro-fecha" value="{{ $filtros['fecha_desde'] ?? '' }}"></div>
                    <div class="filtro-grupo"><label for="fecha_hasta">Fecha hasta</label><input type="date" id="fecha_hasta" name="fecha_hasta" class="filtro-fecha" value="{{ $filtros['fecha_hasta'] ?? '' }}"></div>
                    <div class="filtro-grupo filtros-acciones">
                        <label>&nbsp;</label>
                        <div class="filtros-botones">
                            <button type="submit" class="btn-confirmar-verde btn-buscar">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="{{ route('historial') }}" class="btn-limpiar-filtros">Limpiar</a>
                        </div>
                    </div>
                </form>
                <table class="tabla-datos">
                    <thead><tr><th>Fecha y Hora</th><th>Apartamento</th><th>Residente</th><th>Mensajería</th><th>Guía / Tracking</th><th>Fotografía</th><th>Estado</th><th>Trazabilidad de entrega</th><th style="text-align: center;">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr>
                                <td>{{ $paquete->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $paquete->apartamento?->numero ?? 'Sin apartamento' }} - {{ $paquete->apartamento?->torre ?? 'Sin torre' }}</td>
                                <td>{{ $paquete->residente?->nombre ?? 'Sin residente asignado' }}</td>
                                <td>{{ $paquete->empresa }}</td>
                                <td>{{ $paquete->guia ?? 'Sin guía' }}</td>
                                <td>
                                    @if ($paquete->foto)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paquete->foto) }}" target="_blank" rel="noopener">Ver foto</a>
                                    @else
                                        Sin fotografía
                                    @endif
                                </td>
                                <td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td>
                                <td>
                                    @php($autorizacionActiva = $paquete->autorizacionesRetiro->firstWhere('estado', 'Activa'))
                                    @php($autorizacionReciente = $autorizacionActiva ?? $paquete->autorizacionesRetiro->sortByDesc('id')->first())
                                    @if ($paquete->estado === 'Entregado')
                                        <strong>Fecha:</strong> {{ $paquete->fecha_entrega?->format('d/m/Y H:i') ?? 'Sin registro' }}<br>
                                        <strong>Recibido por:</strong> {{ $paquete->recibido_por ?? 'Sin registro' }}<br>
                                        <strong>Entregado por:</strong> {{ $paquete->entregadoPor?->name ?? 'Sin registro' }}
                                        @php($autorizacionUtilizada = $paquete->autorizacionesRetiro->firstWhere('estado', 'Utilizada'))
                                        @if ($autorizacionUtilizada)
                                            <br><strong>Tercero autorizado:</strong> {{ $autorizacionUtilizada->nombre_autorizado }}<br>
                                            <strong>Documento:</strong> {{ $autorizacionUtilizada->documento_autorizado }}<br>
                                            <strong>Relación:</strong> {{ $autorizacionUtilizada->relacion }}<br>
                                            <strong>Estado autorización:</strong> {{ $autorizacionUtilizada->estado }}
                                        @else
                                            <br><strong>Entrega:</strong> Residente
                                        @endif
                                        @if ($paquete->firma)
                                            <details>
                                                <summary>Ver firma</summary>
                                                <img src="{{ $paquete->firma }}" alt="Firma de recepción" style="max-width: 180px; margin-top: 8px; border: 1px solid #cbd5e1;">
                                            </details>
                                        @endif
                                    @elseif ($autorizacionReciente)
                                        <strong>Autorizado:</strong> {{ $autorizacionReciente->nombre_autorizado }}<br>
                                        <strong>Documento:</strong> {{ $autorizacionReciente->documento_autorizado }}<br>
                                        <strong>Relación:</strong> {{ $autorizacionReciente->relacion }}<br>
                                        <strong>Estado autorización:</strong> {{ $autorizacionReciente->estado }}
                                    @else
                                        Pendiente de entrega
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if ($paquete->estado === 'Pendiente')
                                        <a href="{{ route('entrega', $paquete) }}" class="btn-accion-entregar">Marcar como Entregado</a>
                                    @else
                                        <i class="fas fa-check-circle icono-entregado-check"></i>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No hay paquetes que coincidan con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($paquetes->hasPages())
                    <div class="filtros-botones" style="padding: 20px; justify-content: center;">
                        @if ($paquetes->previousPageUrl())
                            <a href="{{ $paquetes->previousPageUrl() }}" class="btn-limpiar-filtros">Anterior</a>
                        @endif
                        <span>Página {{ $paquetes->currentPage() }} de {{ $paquetes->lastPage() }}</span>
                        @if ($paquetes->nextPageUrl())
                            <a href="{{ $paquetes->nextPageUrl() }}" class="btn-confirmar-verde btn-buscar" style="width: auto;">Siguiente</a>
                        @endif
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
