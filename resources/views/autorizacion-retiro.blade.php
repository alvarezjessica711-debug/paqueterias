<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar Retiro - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Autorizar tercero para retiro</h2><p>La autorización aplica únicamente a este paquete pendiente.</p></div>
            <section class="contenedor-formulario">
                <div class="alerta-exito">
                    <strong>Guía:</strong> {{ $paquete->guia ?? 'Sin guía' }}<br>
                    <strong>Empresa:</strong> {{ $paquete->empresa }}<br>
                    <strong>Apartamento:</strong> {{ $paquete->apartamento?->torre }} - Apto. {{ $paquete->apartamento?->numero }}
                </div>
                <form class="formulario-paquete" method="post" action="{{ route('autorizaciones.store', $paquete) }}">
                    @csrf
                    <div class="grupo-input"><label for="nombre_autorizado">Nombre completo</label><input id="nombre_autorizado" name="nombre_autorizado" value="{{ old('nombre_autorizado') }}" required>@error('nombre_autorizado')<span>{{ $message }}</span>@enderror</div>
                    <div class="grupo-input"><label for="documento_autorizado">Número de documento</label><input id="documento_autorizado" name="documento_autorizado" value="{{ old('documento_autorizado') }}" required>@error('documento_autorizado')<span>{{ $message }}</span>@enderror</div>
                    <div class="grupo-input"><label for="relacion">Parentesco / relación</label><select id="relacion" name="relacion" required><option value="">Selecciona una opción</option>@foreach (['Familiar', 'Esposo/a', 'Hijo/a', 'Padre/Madre', 'Arrendatario', 'Empleado', 'Otro'] as $relacion)<option value="{{ $relacion }}" @selected(old('relacion') === $relacion)>{{ $relacion }}</option>@endforeach</select>@error('relacion')<span>{{ $message }}</span>@enderror</div>
                    <div class="grupo-input full-width"><label for="observacion">Observación</label><textarea id="observacion" name="observacion" placeholder="Información adicional para el vigilante">{{ old('observacion') }}</textarea>@error('observacion')<span>{{ $message }}</span>@enderror</div>
                    <div class="zona-botones full-width"><button type="submit" class="btn-notificar">Autorizar retiro</button><a href="{{ route('mis-paquetes') }}" class="btn-limpiar-firma" style="display: block; text-align: center; text-decoration: none; margin-top: 12px;">Cancelar</a></div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
