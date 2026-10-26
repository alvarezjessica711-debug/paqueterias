<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="dashboard-header-top"><div class="encabezado-seccion"><h2>¡Hola, {{ auth()->user()->name }}!</h2><p class="rol-usuario">@switch(auth()->user()->rol)@case('vigilante')Vigilante @break @case('admin')Administrador @break @case('residente')Residente @break @case('superadmin')Superadministrador @break @default Usuario @endswitch</p><p>Aquí tienes un resumen de las entregas.</p></div></div>
            <div class="dashboard-grid-cards">
                @if (auth()->user()->rol === 'admin')
                    <a href="{{ route('residentes') }}" class="card-dash card-registrar"><i class="fas fa-users"></i><h3>Gestionar<br>Residentes</h3></a>
                    <a href="{{ route('reportes') }}" class="card-dash card-consultar"><i class="fas fa-chart-bar"></i><h3>Consultar<br>Reportes</h3></a>
                @elseif (auth()->user()->rol === 'vigilante')
                <a href="{{ route('registro-paquetes') }}" class="card-dash card-registrar"><i class="fas fa-box-open"></i><h3>Registrar<br>Nuevo Paquete</h3></a>
                <a href="{{ route('consultar') }}" class="card-dash card-consultar"><i class="fas fa-search"></i><h3>Consultar<br>Entregas</h3></a>
                @endif
                @if (auth()->user()->rol === 'superadmin')
                    <a href="{{ route('usuarios') }}" class="card-dash card-registrar"><i class="fas fa-users-cog"></i><h3>Gestionar<br>Usuarios y roles</h3></a>
                @endif
                <a href="{{ route('historial') }}" class="card-dash card-historial"><i class="fas fa-history"></i><h3>Historial de<br>Entregas</h3></a>
            </div>
            <div class="contenedor-tabla">
                <div class="tabla-header"><h3>Últimos paquetes recibidos</h3><a href="{{ route('historial') }}" class="enlace-ver-todos">Ver todos</a></div>
                <table class="tabla-datos">
                    <thead><tr><th>Fecha</th><th>Casa</th><th>Destinatario</th><th>Mensajería</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr>
                                <td>{{ $paquete->created_at->format('d/m/Y') }}</td>
                                <td>{{ $paquete->casa?->codigo ?? 'Código de casa pendiente' }}</td>
                                <td class="texto-largo">{{ $paquete->destinatario ?: ($paquete->residente?->nombre ?? 'Sin destinatario registrado') }}</td>
                                <td class="texto-largo">{{ $paquete->empresa }}</td>
                                <td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No hay paquetes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
