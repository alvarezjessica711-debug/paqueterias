<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="dashboard-header-top"><div class="encabezado-seccion"><h2>¡Hola, Vigilante!</h2><p>Aquí tienes un resumen de las entregas.</p></div></div>
            <div class="dashboard-grid-cards">
                <a href="{{ route('registro-paquetes') }}" class="card-dash card-registrar"><i class="fas fa-box-open"></i><h3>Registrar<br>Nuevo Paquete</h3></a>
                <a href="{{ route('consultar') }}" class="card-dash card-consultar"><i class="fas fa-search"></i><h3>Consultar<br>Entregas</h3></a>
                <a href="{{ route('historial') }}" class="card-dash card-historial"><i class="fas fa-history"></i><h3>Historial de<br>Entregas</h3></a>
            </div>
            <div class="contenedor-tabla">
                <div class="tabla-header"><h3>Últimos paquetes recibidos</h3><a href="{{ route('historial') }}" class="enlace-ver-todos">Ver todos</a></div>
                <table class="tabla-datos">
                    <thead><tr><th>Fecha</th><th>Torre</th><th>Apartamento</th><th>Residente</th><th>Mensajería</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr>
                                <td>{{ $paquete->created_at->format('d/m/Y') }}</td>
                                <td>{{ $paquete->apartamento?->torre ?? 'Sin torre' }}</td>
                                <td>{{ $paquete->apartamento?->numero ?? 'Sin apartamento' }}</td>
                                <td>{{ $paquete->residente?->nombre ?? 'Sin residente asignado' }}</td>
                                <td>{{ $paquete->empresa }}</td>
                                <td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No hay paquetes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
