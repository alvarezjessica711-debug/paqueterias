<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residentes - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion">
                <h2>Directorio de Residentes</h2>
                <p>Gestiona la información de los residentes del conjunto para notificaciones.</p>
            </div>

            @if (session('success'))
                <div class="alerta-exito">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alerta-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section id="formulario-residente" class="contenedor-formulario">
                <form class="formulario-paquete" action="{{ route('residentes.store') }}" method="post">
                    @csrf
                    <div class="grupo-input full-width">
                        <label for="nombre"><i class="fas fa-user"></i> Nombre completo</label>
                        <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required>
                        @error('nombre')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>


                    <div class="grupo-input">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono / WhatsApp</label>
                        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" placeholder="Ej. 310 123 4567">
                        @error('telefono')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grupo-input">
                        <label for="correo"><i class="fas fa-envelope"></i> Correo electrónico</label>
                        <input id="correo" name="correo" type="email" value="{{ old('correo') }}" placeholder="Ej. residente@correo.com">
                        @error('correo')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grupo-input full-width">
                        <label><i class="fas fa-building"></i> Casas asociadas</label>
                        @if ($casas->isNotEmpty())
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; padding: 12px 14px; border: 1px solid #dfe7f1; border-radius: 12px; background: #f8fbff;">
                                @foreach ($casas as $casa)
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #dfe7f1; border-radius: 10px; background: white; cursor: pointer;">
                                        <input type="checkbox" name="casas[]" value="{{ $casa->id }}" {{ collect(old('casas', []))->contains($casa->id) ? 'checked' : '' }}>
                                        <span>{{ $casa->codigo }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="texto-ayuda">Selecciona las casas que pertenecen a este residente.</small>
                        @else
                            <p class="texto-ayuda">Primero registra casas para poder asociarlas a un residente.</p>
                        @endif
                        @error('casas')
                            <span>{{ $message }}</span>
                        @enderror
                        @error('casas.*')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="zona-botones full-width">
                        <button type="submit" class="btn-notificar"><i class="fas fa-user-plus"></i> Registrar residente</button>
                    </div>
                </form>
            </section>

            <div class="contenedor-tabla">

                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Teléfono / WhatsApp</th>
                            <th>Casas asociadas</th>
                            <th>Cuenta / Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($residentes as $residente)
                            <tr>
                                <td class="texto-largo">{{ $residente->nombre }}</td>
                                <td>{{ $residente->telefono ?: 'Sin teléfono registrado' }}</td>
                                <td>
                                    @forelse ($residente->casas as $casa)
                                        <span>{{ $casa->codigo }}@if (! $loop->last), @endif</span>
                                    @empty
                                        <span>Sin casa asociada</span>
                                    @endforelse
                                </td>
                <td>{{ $residente->user ? ($residente->user->activo ? 'Cuenta activa' : 'Cuenta inactiva') : 'Sin cuenta' }} - {{ $residente->activo ? 'Activo' : 'Inactivo' }}</td><td><a href="{{ route('residentes.edit',$residente) }}">Editar</a><form method="post" action="{{ route('residentes.toggle',$residente) }}">@csrf @method('PATCH')<button>{{ $residente->activo?'Desactivar':'Reactivar' }}</button></form></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No hay residentes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
