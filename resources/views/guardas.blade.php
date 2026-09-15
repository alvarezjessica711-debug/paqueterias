<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigilantes - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
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

            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('guardas.store') }}">
                    @csrf
                    <div class="grupo-input"><label for="name"><i class="fas fa-user"></i> Nombre completo</label><input id="name" name="name" value="{{ old('name') }}" placeholder="Nombre completo" required></div>
                    <div class="grupo-input"><label for="email"><i class="fas fa-envelope"></i> Correo</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" required></div>
                    <div class="grupo-input"><label for="password"><i class="fas fa-lock"></i> Contraseña</label><input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" required></div>
                    <div class="grupo-input"><label for="password_confirmation"><i class="fas fa-lock"></i> Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la contraseña" required></div>
                    <div class="zona-botones"><button class="btn-notificar"><i class="fas fa-user-plus"></i> Crear vigilante</button></div>
                </form>
            </section>

            <div class="contenedor-tabla">
                <table class="tabla-datos">
                    <thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($guardas as $guarda)
                            <tr>
                                <td>{{ $guarda->name }}</td>
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
