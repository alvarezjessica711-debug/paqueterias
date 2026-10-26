<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios y roles</title>
    <link rel="stylesheet" href="/estilos.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Usuarios y roles</h2><p>Solo el superadministrador puede crear cuentas y asignar roles.</p></div>
            @if (session('success'))<div class="alerta-exito">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alerta-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('usuarios.store') }}">
                    @csrf
                    <div class="grupo-input"><label for="name">Nombre</label><input id="name" name="name" value="{{ old('name') }}" required></div>
                    <div class="grupo-input"><label for="email">Correo</label><input id="email" name="email" type="email" value="{{ old('email') }}" required></div>
                    <div class="grupo-input"><label for="rol">Rol</label><select id="rol" name="rol" required><option value="">Selecciona</option>@foreach (['admin' => 'Administrador', 'vigilante' => 'Vigilante', 'residente' => 'Residente', 'superadmin' => 'Superadministrador'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected(old('rol') === $valor)>{{ $etiqueta }}</option>@endforeach</select></div>
                    <div class="grupo-input" id="residente-grupo"><label for="residente_id">Residente asociado</label><select id="residente_id" name="residente_id"><option value="">Selecciona</option>@foreach ($residentes as $residente)<option value="{{ $residente->id }}" @selected((string) old('residente_id') === (string) $residente->id)>{{ $residente->nombre }} ({{ $residente->id }})</option>@endforeach</select></div>
                    <div class="grupo-input"><label for="password">Contraseña temporal</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required></div>
                    <div class="grupo-input"><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
                    <div class="zona-botones full-width"><button type="submit" class="btn-notificar">Crear usuario</button></div>
                </form>
            </section>
            <div class="contenedor-tabla"><table class="tabla-datos"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Residente asociado</th><th>Estado</th></tr></thead><tbody>
                @forelse ($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->rol }}</td><td>{{ $user->residente?->nombre ?? '—' }}</td><td>{{ $user->activo ? 'Activo' : 'Inactivo' }}</td></tr>@empty<tr><td colspan="5">No hay usuarios.</td></tr>@endforelse
            </tbody></table></div>
        </main>
    </div>
    <script>
        const rol = document.getElementById('rol');
        const grupo = document.getElementById('residente-grupo');
        const residente = document.getElementById('residente_id');
        function ajustarResidente() { const visible = rol.value === 'residente'; grupo.hidden = !visible; residente.required = visible; if (!visible) residente.value = ''; }
        rol.addEventListener('change', ajustarResidente); ajustarResidente();
    </script>
</body>
</html>
