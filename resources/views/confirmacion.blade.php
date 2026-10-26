<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro confirmado - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="fondo-modal-oscuro">
    <div class="modal-overlay">
        <main class="tarjeta-modal-confirmacion">
            <div class="cabecera-verde-modal"><h3>Registro confirmado</h3><a href="{{ route('dashboard') }}" class="btn-cerrar-x" aria-label="Ir al panel principal"><i class="fas fa-times"></i></a></div>
            <div class="cuerpo-modal-confirmacion" style="text-align: center;">
                <i class="fas fa-check-circle" style="color: #15803d; font-size: 4rem;"></i>
                <h2 style="color: #1e293b; margin: 18px 0 10px;">Paquete registrado</h2>
                <p style="color: #475569; line-height: 1.5; margin-bottom: 24px;">
                    Paquete registrado correctamente.
                    @if (session('whatsapp_estado') === 'accepted')
                        Meta aceptó la solicitud de envío del código por WhatsApp; esto no confirma su entrega al teléfono.
                    @elseif (session('whatsapp_estado') === 'invalid_phone')
                        No se envió WhatsApp: el residente no tiene un teléfono en formato internacional válido.
                    @elseif (session('whatsapp_estado') === 'failed')
                        No se pudo enviar el código por WhatsApp; el proveedor rechazó la solicitud o no respondió.
                    @else
                        WhatsApp todavía no está configurado; no se realizó ningún envío.
                    @endif
                    @if (session('codigo_entrega'))
                        <br><br><strong>Código único de entrega:</strong>
                        <code style="font-size: 1.25rem; letter-spacing: .12em;">{{ session('codigo_entrega') }}</code>
                        <br>Comunica este código al residente por un canal seguro mientras se habilita WhatsApp.
                    @endif
                    @if (session('notificacion_enviada'))
                        <br><br>El aviso por correo también fue procesado por la configuración actual.
                    @endif
                </p>
                <a href="{{ route('registro-paquetes') }}" class="btn-confirmar-verde">Registrar otro paquete</a>
                <a href="{{ route('dashboard') }}" class="btn-secundario" style="display: block; box-sizing: border-box; text-decoration: none; margin-top: 12px;">Volver al panel</a>
            </div>
        </main>
    </div>
</body>
</html>
