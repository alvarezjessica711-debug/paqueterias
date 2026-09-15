<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajería - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        <aside class="sidebar-navegacion">
            <div class="image-container">
                <a href="{{ route('dashboard') }}" title="Volver al panel principal"><img src="{{ asset('Imagenes/logo.png') }}" alt="Logo Valle de San Remo" class="img-logo"></a>
            </div>
            <nav>
                <ul>
                    <li><a href="{{ route('dashboard') }}" class="item-menu"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="{{ route('registro-paquetes') }}" class="item-menu"><i class="fas fa-plus-circle"></i> Registrar Paquete</a></li>
                    <li><a href="{{ route('consultar') }}" class="item-menu"><i class="fas fa-search"></i> Consultar Entregas</a></li>
                    <li><a href="{{ route('historial') }}" class="item-menu"><i class="fas fa-history"></i> Historial</a></li>
                    <li><a href="{{ route('residentes') }}" class="item-menu"><i class="fas fa-user-friends"></i> Residentes</a></li>
                    <li><a href="{{ route('mensajeria') }}" class="item-menu activo"><i class="fas fa-truck"></i> Mensajería</a></li>
                    <li><a href="{{ route('configuracion') }}" class="item-menu"><i class="fas fa-cog"></i> Configuración</a></li>
                </ul>
            </nav>
        </aside>

        <main class="zona-trabajo">
            <div class="encabezado-seccion">
                <h2>Directorio de Mensajerías</h2>
                <p>Gestiona las empresas de envíos y paquetería frecuentes en el conjunto.</p>
            </div>

            @if (session('mensaje'))
                <div style="background-color: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #10b981;">
                    {{ session('mensaje') }}
                </div>
            @endif

            <div class="contenedor-tabla">
                <form class="filtros-historial" method="get" action="{{ route('mensajeria') }}">
                    <div class="filtro-grupo" style="flex: 2;">
                        <label for="busqueda">Buscar por Nombre o Servicio</label>
                        <div class="filtro-input">
                            <i class="fas fa-search"></i>
                            <input type="text" id="busqueda" name="busqueda" placeholder="Ej. Servientrega, Coordinadora..." value="{{ $busqueda['busqueda'] ?? '' }}">
                        </div>
                    </div>
                    <div class="filtros-botones-consultar">
                        <button type="submit" class="btn-confirmar-verde btn-buscar"><i class="fas fa-search"></i> Buscar</button>
                        <a href="{{ route('mensajeria') }}" class="btn-limpiar-filtros">Limpiar</a>
                        <a href="{{ route('mensajeria.crear') }}" class="btn-confirmar-verde" style="text-decoration: none;"><i class="fas fa-plus-circle"></i> Agregar</a>
                    </div>
                </form>

                @if ($mensajerias->count() > 0)
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo de Servicio</th>
                                <th>Línea de Atención</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mensajerias as $mensajeria)
                                <tr>
                                    <td><strong>{{ $mensajeria->nombre }}</strong></td>
                                    <td>{{ $mensajeria->tipo_servicio ?? '-' }}</td>
                                    <td>{{ $mensajeria->linea_atencion ?? '-' }}</td>
                                    <td>{{ $mensajeria->email ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ strtolower($mensajeria->estado) }}" style="@if($mensajeria->estado === 'Activa') background-color: #dcfce7; color: #15803d; border-color: #bbf7d0; @else background-color: #fee2e2; color: #dc2626; border-color: #fecaca; @endif">
                                            {{ $mensajeria->estado }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('mensajeria.editar', $mensajeria) }}" style="color: #2563eb; text-decoration: none; margin-right: 15px;" title="Editar"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('mensajeria.destroy', $mensajeria) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Deseas eliminar esta mensajería?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="color: #ef4444; text-decoration: none; border: none; background: none; cursor: pointer;" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($mensajerias->hasPages())
                        <div class="paginacion-tabla">
                            @if ($mensajerias->onFirstPage())
                                <span style="opacity: 0.5;"><i class="fas fa-chevron-left"></i></span>
                            @else
                                <a href="{{ $mensajerias->previousPageUrl() }}" class="page-item"><i class="fas fa-chevron-left"></i></a>
                            @endif

                            @foreach ($mensajerias->getUrlRange(1, $mensajerias->lastPage()) as $page => $url)
                                @if ($page == $mensajerias->currentPage())
                                    <span class="page-item activo">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="page-item">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($mensajerias->hasMorePages())
                                <a href="{{ $mensajerias->nextPageUrl() }}" class="page-item"><i class="fas fa-chevron-right"></i></a>
                            @else
                                <span style="opacity: 0.5;"><i class="fas fa-chevron-right"></i></span>
                            @endif
                        </div>
                    @endif
                @else
                    <div style="padding: 40px 26px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <p style="font-size: 1.1rem;">No hay mensajerías registradas aún.</p>
                        <a href="{{ route('mensajeria.crear') }}" class="btn-confirmar-verde" style="display: inline-block; margin-top: 1rem; text-decoration: none; padding: 0.75rem 1.5rem;"><i class="fas fa-plus-circle"></i> Agregar Primera Mensajería</a>
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
