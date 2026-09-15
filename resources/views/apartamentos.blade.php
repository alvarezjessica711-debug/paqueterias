<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apartamentos - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')
        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Apartamentos</h2><p>Administra las torres y apartamentos disponibles en el conjunto.</p></div>
            @if (session('success'))<div class="alerta-exito">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alerta-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="contenedor-formulario">
                <form class="formulario-paquete" method="post" action="{{ route('apartamentos.store') }}">
                    @csrf
                    <div class="grupo-input"><label for="torre"><i class="fas fa-building"></i> Torre</label><input id="torre" name="torre" value="{{ old('torre') }}" placeholder="Ej. Torre 1" required></div>
                    <div class="grupo-input"><label for="numero"><i class="fas fa-door-closed"></i> Número de apartamento</label><input id="numero" name="numero" value="{{ old('numero') }}" placeholder="Ej. 301" required></div>
                    <div class="zona-botones"><button type="submit" class="btn-notificar"><i class="fas fa-plus-circle"></i> Crear apartamento</button></div>
                </form>
            </section>

            <div class="contenedor-tabla">
                <table class="tabla-datos">
                    <thead><tr><th>Torre</th><th>Apartamento</th><th>Estado</th><th>Residentes asociados</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($apartamentos as $apartamento)
                            <tr>
                                <td>{{ $apartamento->torre }}</td><td>{{ $apartamento->numero }}</td>
                                <td><span class="badge {{ $apartamento->activo ? 'badge-activo' : 'badge-inactivo' }}">{{ $apartamento->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="residentes-asociados">{{ $apartamento->residentes->pluck('nombre')->join(', ') ?: 'Sin residentes asociados' }}</td>
                                <td class="acciones-columna">
                                    <button type="button" class="btn-accion-entregar" data-editar-apartamento="{{ $apartamento->id }}">Editar</button>
                                    <form class="form-accion-tabla" method="post" action="{{ route('apartamentos.toggle', $apartamento) }}">@csrf @method('PATCH')<button type="submit" class="btn-limpiar-filtros">{{ $apartamento->activo ? 'Desactivar' : 'Reactivar' }}</button></form>
                                </td>
                            </tr>
                            <tr id="editar-apartamento-{{ $apartamento->id }}" class="fila-edicion-apartamento" hidden>
                                <td colspan="5">
                                    <form class="formulario-edicion-apartamento" method="post" action="{{ route('apartamentos.update', $apartamento) }}">
                                        @csrf @method('PATCH')
                                        <div class="grupo-input"><label for="torre-{{ $apartamento->id }}">Torre</label><input id="torre-{{ $apartamento->id }}" name="torre" value="{{ $apartamento->torre }}" required></div>
                                        <div class="grupo-input"><label for="numero-{{ $apartamento->id }}">Número de apartamento</label><input id="numero-{{ $apartamento->id }}" name="numero" value="{{ $apartamento->numero }}" required></div>
                                        <div class="acciones-edicion-apartamento"><button type="submit" class="btn-confirmar-verde">Guardar cambios</button><button type="button" class="btn-limpiar-filtros" data-cancelar-edicion="{{ $apartamento->id }}">Cancelar</button></div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No hay apartamentos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-editar-apartamento]').forEach((boton) => boton.addEventListener('click', () => {
            document.getElementById('editar-apartamento-' + boton.dataset.editarApartamento).hidden = false;
        }));
        document.querySelectorAll('[data-cancelar-edicion]').forEach((boton) => boton.addEventListener('click', () => {
            document.getElementById('editar-apartamento-' + boton.dataset.cancelarEdicion).hidden = true;
        }));
    </script>
</body>
</html>
