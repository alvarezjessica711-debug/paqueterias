<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría del paquete #{{ $paquete->id }}</title>
    <link rel="stylesheet" href="/estilos.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Auditoría del paquete #{{ $paquete->id }}</h2><p>Registro de modificaciones y entrega.</p></div>
            <div class="contenedor-tabla">
                <table class="tabla-datos">
                    <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead>
                    <tbody>
                        @forelse ($auditorias as $evento)
                            <tr><td>{{ $evento->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $evento->usuario?->name ?? 'Usuario no disponible' }}</td><td>{{ $evento->accion }}</td><td>{{ $evento->campo ?? '—' }}</td><td>{{ $evento->valor_anterior ?? '—' }}</td><td>{{ $evento->valor_nuevo ?? '—' }}</td></tr>
                        @empty
                            <tr><td colspan="6">No hay auditoría previa para este paquete.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
