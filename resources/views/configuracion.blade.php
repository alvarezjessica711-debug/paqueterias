<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion"><h2>Configuración</h2><p>Actualiza tu información personal y los mensajes automáticos del portero.</p></div>

            @if (session('success_perfil'))
                <div class="alerta-exito">{{ session('success_perfil') }}</div>
            @endif

            @if (session('success_mensaje'))
                <div class="alerta-exito">{{ session('success_mensaje') }}</div>
            @endif

            @if (session('success_password'))
                <div class="alerta-exito">{{ session('success_password') }}</div>
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

            <div style="display: flex; gap: 25px; flex-wrap: wrap; max-width: 1000px; width: 100%;">
                <div class="contenedor-formulario" style="flex: 1; min-width: 350px; margin: 0;">
                    <h3 style="color: var(--azul-sistema); margin-bottom: 20px; font-size: 1.15rem;"><i class="fas fa-user-shield" style="color: var(--verde-sena); margin-right: 8px;"></i> Perfil del Personal</h3>
                    <form class="formulario-paquete" method="POST" action="{{ route('configuracion.perfil') }}">
                        @csrf
                        <div class="grupo-input">
                            <label for="nombre">Nombre Completo</label>
                            <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $perfil['nombre'] ?? auth()->user()?->name ?? 'Carlos Mendoza') }}" placeholder="Tu nombre" required>
                        </div>
                        <div class="grupo-input">
                            <label for="turno">Turno Asignado</label>
                            <select id="turno" name="turno" required>
                                <option value="Mañana (6:00 AM - 2:00 PM)" @selected((old('turno', $perfil['turno'] ?? 'Tarde (2:00 PM - 10:00 PM)')) === 'Mañana (6:00 AM - 2:00 PM)')>Mañana (6:00 AM - 2:00 PM)</option>
                                <option value="Tarde (2:00 PM - 10:00 PM)" @selected((old('turno', $perfil['turno'] ?? 'Tarde (2:00 PM - 10:00 PM)')) === 'Tarde (2:00 PM - 10:00 PM)')>Tarde (2:00 PM - 10:00 PM)</option>
                                <option value="Noche (10:00 PM - 6:00 AM)" @selected((old('turno', $perfil['turno'] ?? 'Tarde (2:00 PM - 10:00 PM)')) === 'Noche (10:00 PM - 6:00 AM)')>Noche (10:00 PM - 6:00 AM)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-confirmar-verde" style="margin-top: 10px;">Actualizar Perfil</button>
                    </form>
                </div>

                <div class="contenedor-formulario" style="flex: 1; min-width: 350px; margin: 0;">
                    <h3 style="color: var(--azul-sistema); margin-bottom: 20px; font-size: 1.15rem;"><i class="fab fa-whatsapp" style="color: var(--verde-sena); margin-right: 8px;"></i> Mensajes de WhatsApp</h3>
                    <form class="formulario-paquete" method="POST" action="{{ route('configuracion.mensaje') }}">
                        @csrf
                        <div class="grupo-input">
                            <label for="plantilla">Plantilla de Nuevo Paquete</label>
                            <textarea id="plantilla" name="plantilla" style="min-height: 120px;" required>{{ old('plantilla', $mensaje['plantilla']) }}</textarea>
                        </div>
                        <div class="grupo-input" style="flex-direction: row; align-items: center; gap: 10px; margin-top: 5px;">
                            <input type="checkbox" id="activar_wpp" name="activar_wpp" value="1" {{ old('activar_wpp', $mensaje['activar_wpp'] ? '1' : '0') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            <label for="activar_wpp" style="margin: 0; cursor: pointer;">Activar envío automático al registrar</label>
                        </div>
                        <button type="submit" class="btn-confirmar-verde" style="margin-top: 10px;">Guardar Mensaje</button>
                    </form>
                </div>
            </div>

            <div class="contenedor-formulario" style="max-width: 1000px; width: 100%; margin-top: 25px; margin-bottom: 40px;">
                <h3 style="color: var(--azul-sistema); margin-bottom: 20px; font-size: 1.15rem;"><i class="fas fa-lock" style="color: var(--verde-sena); margin-right: 8px;"></i> Cambiar contraseña</h3>
                <form class="formulario-paquete" method="POST" action="{{ route('configuracion.password') }}" style="flex-direction: row; flex-wrap: wrap; align-items: flex-end; gap: 20px;">
                    @csrf
                    <div class="grupo-input" style="flex: 1; min-width: 200px;">
                        <label for="password_actual">Contraseña Actual</label>
                        <input id="password_actual" name="password_actual" type="password" placeholder="••••••••" required>
                    </div>
                    <div class="grupo-input" style="flex: 1; min-width: 200px;">
                        <label for="password_nueva">Nueva Contraseña</label>
                        <input id="password_nueva" name="password_nueva" type="password" placeholder="••••••••" required>
                    </div>
                    <div class="grupo-input" style="flex: 1; min-width: 200px;">
                        <label for="password_nueva_confirmation">Confirmar Contraseña</label>
                        <input id="password_nueva_confirmation" name="password_nueva_confirmation" type="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-limpiar-firma" style="margin: 0; flex: 0.5; min-width: 180px; height: 46px;">Cambiar Contraseña</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
