<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Entregas - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Consultar Entregas</h2><p>Busca rápidamente si hay algún paquete esperando en la portería.</p></div>
            <div class="contenedor-tabla panel-busqueda">
                <form class="filtros-historial" method="get" action="{{ route('consultar') }}">
                    <div class="filtro-grupo"><label for="busqueda">Ingresa apartamento, torre, residente o guía</label><div class="filtro-input"><i class="fas fa-search"></i><input id="busqueda" type="text" name="busqueda" placeholder="Ej. 204, Torre 2, María López o guía" value="{{ $busqueda['busqueda'] ?? '' }}"></div></div>
                    <div class="filtros-botones-consultar">
                        <button type="submit" class="btn-confirmar-verde btn-buscar"><i class="fas fa-search"></i> Buscar</button>
                        <a href="{{ route('consultar') }}" class="btn-limpiar-filtros">Limpiar</a>
                    </div>
                </form>
            </div>
            <div class="contenedor-tabla">
                <div class="tabla-header">
                    <h3>@if (!empty($busqueda['busqueda']))
                        Resultados de búsqueda
                    @else
                        Entregas pendientes
                    @endif</h3>
                    @if ($paquetes->count() > 0)
                        <span class="badge pendiente">{{ $paquetes->count() }} {{ $paquetes->count() === 1 ? 'pendiente' : 'pendientes' }}</span>
                    @endif
                </div>
                @if ($paquetes->count() > 0)
                    <table class="tabla-datos">
                        <thead><tr><th>Fecha</th><th>Apartamento</th><th>Residente</th><th>Mensajería</th><th>Guía / Tracking</th><th>Autorización</th><th>Fotografía</th><th>Estado</th><th>Acción</th></tr></thead>
                        <tbody>
                            @foreach ($paquetes as $paquete)
                                <tr>
                                    <td>{{ $paquete->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $paquete->apartamento?->numero ?? 'Sin apartamento' }} - {{ $paquete->apartamento?->torre ?? 'Sin torre' }}</td>
                                    <td>{{ $paquete->residente?->nombre ?? 'Sin residente' }}</td>
                                    <td>{{ $paquete->empresa }}</td>
                                    <td>{{ $paquete->guia ?? 'Sin guía' }}</td>
                                    <td>
                                        @if ($paquete->autorizacionActiva)
                                            <strong>Tercero autorizado</strong><br>
                                            {{ $paquete->autorizacionActiva->nombre_autorizado }}<br>
                                            Doc: {{ $paquete->autorizacionActiva->documento_autorizado }}<br>
                                            {{ $paquete->autorizacionActiva->relacion }}
                                        @else
                                            Sin autorización activa
                                        @endif
                                    </td>
                                    <td>
                                        @if ($paquete->foto)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paquete->foto) }}" target="_blank" rel="noopener">Ver foto</a>
                                        @else
                                            Sin fotografía
                                        @endif
                                    </td>
                                    <td><span class="badge pendiente">{{ $paquete->estado }}</span></td>
                                    <td><a href="{{ route('entrega', $paquete) }}" class="btn-accion-entregar">Entregar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 40px 26px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <p style="font-size: 1.1rem;">@if (!empty($busqueda['busqueda']))
                            No se encontraron paquetes con esos criterios.
                        @else
                            No hay paquetes pendientes en este momento.
                        @endif</p>
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
