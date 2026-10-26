<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar residente - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Editar residente</h2><p>Actualiza los datos y casas del residente.</p></div>
            @if ($errors->any())
                <div class="alerta-error" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('residentes.update', $residente) }}">
                    @csrf
                    @method('PATCH')
                    <div class="grupo-input">
                        <label for="nombre">Nombre completo</label>
                        <input id="nombre" name="nombre" value="{{ old('nombre', $residente->nombre) }}" required>
                    </div>
                    <div class="grupo-input">
                        <label for="telefono">Teléfono</label>
                        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $residente->telefono) }}">
                    </div>
                    <div class="grupo-input">
                        <label for="correo">Correo de contacto</label>
                        <input id="correo" name="correo" type="email" value="{{ old('correo', $residente->correo) }}">
                    </div>
                    <div class="grupo-input" role="group" aria-labelledby="casas-label">
                        <span id="casas-label">Casas asociadas</span>
                        @foreach ($casas as $casa)
                            <label for="casa-{{ $casa->id }}">
                                <input id="casa-{{ $casa->id }}" type="checkbox" name="casas[]" value="{{ $casa->id }}" @checked(in_array($casa->id, old('casas', $residente->casas->modelKeys())))>
                                {{ $casa->codigo }}
                            </label>
                        @endforeach
                    </div>
                    @if ($residente->user)
                        <div class="grupo-input">
                            <label for="email_acceso">Correo de acceso</label>
                            <input id="email_acceso" name="email_acceso" type="email" value="{{ old('email_acceso', $residente->user->email) }}">
                        </div>
                        <div class="grupo-input">
                            <label for="password">Nueva contraseña (opcional)</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Dejar vacía para conservar la actual">
                        </div>
                        <div class="grupo-input">
                            <label for="password_confirmation">Confirmar nueva contraseña</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        </div>
                    @endif
                    <button type="submit" class="btn-notificar">Actualizar residente</button>
                    <a href="{{ route('residentes') }}" class="btn-limpiar-filtros">Cancelar</a>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
