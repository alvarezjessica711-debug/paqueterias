<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar vigilante - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Editar vigilante</h2><p>Actualiza la información de la cuenta de vigilancia.</p></div>
            @if ($errors->any())
                <div class="alerta-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('guardas.update', $user) }}">
                    @csrf
                    @method('PATCH')
                    <div class="grupo-input"><label for="name"><i class="fas fa-user"></i> Nombre completo</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
                    <div class="grupo-input"><label for="email"><i class="fas fa-envelope"></i> Correo</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required></div>
                    <div class="grupo-input"><label for="password"><i class="fas fa-lock"></i> Nueva contraseña</label><input id="password" name="password" type="password" placeholder="Opcional"></div>
                    <div class="grupo-input"><label for="password_confirmation"><i class="fas fa-lock"></i> Confirmar nueva contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la contraseña"></div>
                    <div class="zona-botones"><button type="submit" class="btn-notificar"><i class="fas fa-save"></i> Actualizar vigilante</button></div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
