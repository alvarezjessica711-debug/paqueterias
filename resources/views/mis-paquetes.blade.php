<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis paquetes - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Mis paquetes</h2><p>Consulta únicamente los paquetes asociados a ti.</p></div>
            <div class="contenedor-tabla">
                @if (session('success'))
                    <div class="alerta-exito">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alerta-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form class="filtros-historial" method="get" action="{{ route('mis-paquetes') }}">
                    <div class="filtro-grupo"><label for="estado">Estado</label><select id="estado" name="estado" class="filtro-select"><option value="Todos" @selected(($filtros['estado'] ?? 'Todos') === 'Todos')>Todos</option><option value="Pendiente" @selected(($filtros['estado'] ?? null) === 'Pendiente')>Pendiente</option><option value="Entregado" @selected(($filtros['estado'] ?? null) === 'Entregado')>Entregado</option></select></div>
                    <div class="filtro-grupo"><label for="mes">Mes</label><input type="month" id="mes" name="mes" class="filtro-fecha" value="{{ $filtros['mes'] ?? now()->format('Y-m') }}"></div>
                    <div class="filtro-grupo"><label for="fecha">Fecha exacta</label><input type="date" id="fecha" name="fecha" class="filtro-fecha" value="{{ $filtros['fecha'] ?? '' }}"></div>
                    <div class="filtro-grupo filtros-acciones"><label>&nbsp;</label><div class="filtros-botones"><button type="submit" class="btn-confirmar-verde btn-buscar"><i class="fas fa-search"></i> Buscar</button><a href="{{ route('mis-paquetes') }}" class="btn-limpiar-filtros">Limpiar</a></div></div>
                </form>
                <table class="tabla-datos">
                    <thead><tr><th>Fecha</th><th>Guía / Tracking</th><th>Empresa transportadora</th><th>Apartamento</th><th>Detalles</th><th>Fotografía</th><th>Estado</th><th>Entrega</th><th>Autorización</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr><td>{{ $paquete->created_at->format('d/m/Y H:i') }}</td><td>{{ $paquete->guia ?? 'Sin guía' }}</td><td>{{ $paquete->empresa }}</td><td>{{ $paquete->apartamento?->torre ?? 'Sin torre' }} - {{ $paquete->apartamento?->numero ?? 'Sin apartamento' }}</td><td>{{ $paquete->detalles ?? 'Sin detalles' }}</td><td>@if ($paquete->foto)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paquete->foto) }}" target="_blank" rel="noopener">Ver foto</a>@else Sin fotografía @endif</td><td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td><td>@if ($paquete->estado === 'Entregado') Entregado el {{ $paquete->fecha_entrega?->format('d/m/Y H:i') ?? 'Sin registro' }}<br>Recibido por: {{ $paquete->recibido_por ?? 'Sin registro' }} @else Pendiente de entrega @endif</td><td>@if ($paquete->estado === 'Pendiente') @if ($paquete->autorizacionActiva) <strong>Autorizado para retiro</strong><br>Nombre: {{ $paquete->autorizacionActiva->nombre_autorizado }}<br>Documento: {{ $paquete->autorizacionActiva->documento_autorizado }}<br>Relación: {{ $paquete->autorizacionActiva->relacion }}<br><form method="post" action="{{ route('autorizaciones.cancelar', [$paquete, $paquete->autorizacionActiva]) }}" style="margin-top: 8px;">@csrf @method('PATCH')<button type="submit" class="btn-limpiar-filtros">Cancelar autorización</button></form> @else <a href="{{ route('autorizaciones.create', $paquete) }}" class="btn-accion-entregar">Autorizar tercero</a> @endif @else Sin autorización activa @endif</td></tr>
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
