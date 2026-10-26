<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Entregas - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Historial de Entregas</h2><p>Consulta el historial de paquetes registrados.</p></div>
            <div class="contenedor-tabla historial-panel">
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
                                placeholder="Guía, residente, casa o empresa"
                            >
                        </div>
                    </div>
                    <div class="filtro-grupo"><label for="residente">Residente</label><input type="text" id="residente" name="residente" class="filtro-fecha" value="{{ $filtros['residente'] ?? '' }}" placeholder="Nombre del residente"></div>
                    <div class="filtro-grupo"><label for="casa">Código de casa</label><input type="text" id="casa" name="casa" class="filtro-fecha" value="{{ $filtros['casa'] ?? '' }}" placeholder="Ej. A1"></div>
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
                <div class="historial-tabla-scroll" role="region" aria-label="Tabla de historial de paquetes" tabindex="0">
                <table class="tabla-datos tabla-historial">
                    <colgroup><col class="col-fecha"><col class="col-casa"><col class="col-residente"><col class="col-mensajeria"><col class="col-guia"><col class="col-fotografia"><col class="col-estado"><col class="col-trazabilidad"><col class="col-acciones"></colgroup>
                    <thead><tr><th>Fecha y Hora</th><th>Casa</th><th>Destinatario</th><th>Mensajería</th><th>Guía / Tracking</th><th>Fotografía</th><th>Estado</th><th>Trazabilidad de entrega</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr>
                                <td>{{ $paquete->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $paquete->casa?->codigo ?? 'Código de casa pendiente' }}</td>
                                <td class="texto-largo">{{ $paquete->destinatario ?: ($paquete->residente?->nombre ?? 'Sin destinatario registrado') }}</td>
                                <td class="texto-largo">{{ $paquete->empresa }}</td>
                                <td>{{ $paquete->guia ?? 'Sin guía' }}</td>
                                <td>
                                    @if ($paquete->foto)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paquete->foto) }}" target="_blank" rel="noopener">Ver foto</a>
                                    @else
                                        Sin fotografía
                                    @endif
                                </td>
                                <td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td>
                                <td class="texto-largo">
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
                                    @elseif ($autorizacionReciente)
                                        <strong>Autorizado:</strong> {{ $autorizacionReciente->nombre_autorizado }}<br>
                                        <strong>Documento:</strong> {{ $autorizacionReciente->documento_autorizado }}<br>
                                        <strong>Relación:</strong> {{ $autorizacionReciente->relacion }}<br>
                                        <strong>Estado autorización:</strong> {{ $autorizacionReciente->estado }}
                                    @else
                                        Pendiente de entrega
                                    @endif
                                </td>
                                <td>
                                    <div class="acciones-historial">
                                    @if (auth()->user()->rol === 'vigilante' && $paquete->estado === 'Pendiente')
                                        <a href="{{ route('entrega', $paquete) }}" class="btn-accion-entregar">Marcar como Entregado</a>
                                        <a href="{{ route('paquetes.edit', $paquete) }}" class="btn-limpiar-filtros">Corregir</a>
                                    @elseif ($paquete->estado === 'Entregado')
                                        <i class="fas fa-check-circle icono-entregado-check"></i>
                                    @endif
                                    @if (in_array(auth()->user()->rol, ['admin', 'vigilante', 'superadmin'], true))
                                        <a href="{{ route('paquetes.auditoria', $paquete) }}" class="enlace-auditoria">Ver auditoría</a>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No hay paquetes que coincidan con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
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
                @if (in_array(auth()->user()->rol, ['admin', 'superadmin'], true))
                    <section class="historial-auditoria">
                        <h3>Auditoría de modificaciones y entregas</h3>
                        <div class="historial-tabla-scroll" role="region" aria-label="Tabla de auditoría de modificaciones y entregas" tabindex="0"><table class="tabla-datos tabla-auditoria">
                            <thead><tr><th>Fecha y hora</th><th>Paquete</th><th>Usuario</th><th>Acción</th><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead>
                            <tbody>
                                @forelse ($auditorias as $evento)
                                    <tr><td>{{ $evento->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $evento->paquete_id }}</td><td>{{ $evento->usuario?->name ?? 'Usuario no disponible' }}</td><td>{{ $evento->accion }}</td><td>{{ $evento->campo ?? '—' }}</td><td>{{ $evento->valor_anterior ?? '—' }}</td><td>{{ $evento->valor_nuevo ?? '—' }}</td></tr>
                                @empty
                                    <tr><td colspan="7">No hay eventos de auditoría registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table></div>
                        {{ $auditorias->links() }}
                    </section>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
