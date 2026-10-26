<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigilantes - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion">
                <h2>Vigilantes</h2>
                <p>Administra las cuentas del personal de vigilancia del conjunto.</p>
            </div>

            @if (session('success'))
                <div class="alerta-exito">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alerta-error" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="contenedor-tabla">
                <table class="tabla-datos">
                    <thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($guardas as $guarda)
                            <tr>
                                <td class="texto-largo">{{ $guarda->name }}</td>
                                <td>{{ $guarda->email }}</td>
                                <td><span class="badge {{ $guarda->activo ? 'badge-activo' : 'badge-inactivo' }}">{{ $guarda->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="acciones-columna">
                                    <a class="btn-accion-entregar" href="{{ route('guardas.edit', $guarda) }}">Editar</a>
                                    <form class="form-accion-tabla" method="post" action="{{ route('guardas.toggle', $guarda) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-limpiar-filtros">{{ $guarda->activo ? 'Desactivar' : 'Reactivar' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No hay vigilantes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
