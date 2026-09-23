<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta de residente - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Crear cuenta de residente</h2><p>Configura el acceso del residente al sistema.</p></div>
            @if ($errors->any())
                <div class="alerta-error" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('residentes.cuenta.store', $residente) }}">
                    @csrf
                    <p><strong>{{ $residente->nombre }}</strong><br>{{ $residente->apartamentos->map(fn ($apartamento) => $apartamento->torre.' - Apto. '.$apartamento->numero)->join(', ') }}</p>
                    <div class="grupo-input">
                        <label for="email">Correo de acceso</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $residente->correo) }}" required>
                    </div>
                    <div class="grupo-input">
                        <label for="password">Contraseña</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required>
                    </div>
                    <div class="grupo-input">
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn-notificar">Crear cuenta</button>
                    <a href="{{ route('residentes') }}" class="btn-limpiar-filtros">Cancelar</a>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
