<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casas - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Casas</h2><p>Administra los códigos de casa disponibles en el conjunto.</p></div>
            @if (session('success'))<div class="alerta-exito">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alerta-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('casas.store') }}">
                    @csrf
                    <div class="grupo-input"><label for="codigo"><i class="fas fa-house"></i> Código de casa</label><input id="codigo" name="codigo" value="{{ old('codigo') }}" placeholder="Ej. A1" pattern="[A-F][1-9][0-9]*" title="Usa una letra de manzana A-F seguida del número de casa." required></div>
                    <div class="zona-botones"><button type="submit" class="btn-notificar"><i class="fas fa-plus-circle"></i> Crear casa</button></div>
                </form>
            </section>

            <div class="contenedor-tabla tabla-casas">
                <table class="tabla-datos">
                    <thead><tr><th>Código de casa</th><th>Estado</th><th>Residentes asociados</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($casas as $casa)
                            <tr>
                                <td>{{ $casa->codigo }}@if (str_starts_with($casa->codigo, 'LEG-'))<br><small>Código provisional: asigna la manzana correcta.</small>@endif</td>
                                <td><span class="badge {{ $casa->activo ? 'badge-activo' : 'badge-inactivo' }}">{{ $casa->activo ? 'Activa' : 'Inactiva' }}</span></td>
                                <td class="texto-largo residentes-asociados">{{ $casa->residentes->pluck('nombre')->join(', ') ?: 'Sin residentes asociados' }}</td>
                                <td class="acciones-columna">
                                    <button type="button" class="btn-accion-entregar" data-editar-casa="{{ $casa->id }}">Editar código</button>
                                    <form class="form-accion-tabla" method="post" action="{{ route('casas.toggle', $casa) }}">@csrf @method('PATCH')<button type="submit" class="btn-limpiar-filtros">{{ $casa->activo ? 'Retirar' : 'Reactivar' }}</button></form>
                                </td>
                            </tr>
                            <tr id="editar-casa-{{ $casa->id }}" class="fila-edicion-casa" hidden>
                                <td colspan="4">
                                    <form class="formulario-edicion-casa" method="post" action="{{ route('casas.update', $casa) }}">
                                        @csrf @method('PATCH')
                                        <div class="grupo-input"><label for="codigo-{{ $casa->id }}">Código de casa</label><input id="codigo-{{ $casa->id }}" name="codigo" value="{{ str_starts_with($casa->codigo, 'LEG-') ? '' : $casa->codigo }}" placeholder="Ej. A1" pattern="[A-F][1-9][0-9]*" title="Usa una letra de manzana A-F seguida del número de casa." required></div>
                                        <div class="acciones-edicion-casa"><button type="submit" class="btn-confirmar-verde">Guardar cambios</button><button type="button" class="btn-limpiar-filtros" data-cancelar-edicion="{{ $casa->id }}">Cancelar</button></div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No hay casas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-editar-casa]').forEach((boton) => boton.addEventListener('click', () => {
            document.getElementById('editar-casa-' + boton.dataset.editarCasa).hidden = false;
            boton.closest('.contenedor-tabla').scrollLeft = 0;
        }));
        document.querySelectorAll('[data-cancelar-edicion]').forEach((boton) => boton.addEventListener('click', () => {
            document.getElementById('editar-casa-' + boton.dataset.cancelarEdicion).hidden = true;
        }));
    </script>
</body>
</html>
