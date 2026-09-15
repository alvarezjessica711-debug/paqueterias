<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi panel - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion">
                <h2>Hola, {{ $residenteNombre }}</h2>
                <p>Consulta el estado de tus paquetes recibidos durante este mes.</p>
            </div>
            <div class="dashboard-grid-cards">
                <div class="card-dash card-consultar"><i class="fas fa-box"></i><h3>Paquetes pendientes<br>{{ $pendientes }}</h3></div>
                <div class="card-dash card-historial"><i class="fas fa-check-circle"></i><h3>Entregados este mes<br>{{ $entregados }}</h3></div>
                <div class="card-dash card-registrar"><i class="fas fa-boxes-stacked"></i><h3>Total del mes<br>{{ $totalMes }}</h3></div>
            </div>
            <div class="contenedor-tabla">
                <div class="tabla-header"><h3>Paquetes recientes</h3><a href="{{ route('mis-paquetes') }}" class="enlace-ver-todos">Ver mis paquetes</a></div>
                <table class="tabla-datos">
                    <thead><tr><th>Fecha</th><th>Apartamento</th><th>Mensajería</th><th>Detalles</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse ($paquetes as $paquete)
                            <tr>
                                <td>{{ $paquete->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $paquete->apartamento?->torre ?? 'Sin torre' }} - {{ $paquete->apartamento?->numero ?? 'Sin apartamento' }}</td>
                                <td>{{ $paquete->empresa }}</td>
                                <td>{{ $paquete->detalles ?? 'Sin detalles' }}</td>
                                <td><span class="badge {{ strtolower($paquete->estado) }}">{{ $paquete->estado }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No tienes paquetes registrados este mes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>