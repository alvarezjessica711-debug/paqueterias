<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro confirmado - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="fondo-modal-oscuro">
    <div class="modal-overlay">
        <main class="tarjeta-modal-firma">
            <div class="cabecera-verde-modal"><h3>Registro confirmado</h3><a href="{{ route('dashboard') }}" class="btn-cerrar-x" aria-label="Ir al panel principal"><i class="fas fa-times"></i></a></div>
            <div class="cuerpo-modal-firma" style="text-align: center;">
                <i class="fas fa-check-circle" style="color: #15803d; font-size: 4rem;"></i>
                <h2 style="color: #1e293b; margin: 18px 0 10px;">Paquete registrado</h2>
                <p style="color: #475569; line-height: 1.5; margin-bottom: 24px;">
                    @if (session('notificacion_enviada'))
                        Paquete registrado correctamente y residente notificado.
                    @else
                        Paquete registrado correctamente, pero no fue posible enviar la notificación al residente.
                    @endif
                </p>
                <a href="{{ route('registro-paquetes') }}" class="btn-confirmar-verde">Registrar otro paquete</a>
                <a href="{{ route('dashboard') }}" class="btn-limpiar-firma" style="display: block; box-sizing: border-box; text-decoration: none; margin-top: 12px;">Volver al panel</a>
            </div>
        </main>
    </div>
</body>
</html>
