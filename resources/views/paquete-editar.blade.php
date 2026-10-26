<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corregir paquete</title>
    <link rel="stylesheet" href="/estilos.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Corregir paquete #{{ $paquete->id }}</h2><p>Las correcciones quedan registradas en el historial.</p></div>
            <section class="contenedor-formulario">
                <form class="formulario-paquete" action="{{ route('paquetes.update', $paquete) }}" method="post">
                    @csrf
                    @method('PATCH')
                    @foreach (['empresa' => 'Empresa de mensajería', 'guia' => 'Guía / tracking', 'destinatario' => 'Destinatario'] as $campo => $etiqueta)
                        <div class="grupo-input full-width">
                            <label for="{{ $campo }}">{{ $etiqueta }}</label>
                            <input id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $paquete->$campo) }}" @if ($campo === 'destinatario') maxlength="255" @endif required>
                            @error($campo)<span>{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                    <div class="grupo-input full-width">
                        <label for="detalles">Detalles</label>
                        <textarea id="detalles" name="detalles">{{ old('detalles', $paquete->detalles) }}</textarea>
                        @error('detalles')<span>{{ $message }}</span>@enderror
                    </div>
                    <div class="zona-botones full-width"><button type="submit" class="btn-notificar">Guardar corrección</button><a href="{{ route('historial') }}">Cancelar</a></div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
