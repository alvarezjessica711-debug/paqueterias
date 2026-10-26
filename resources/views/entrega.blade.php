<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar entrega - Valle de San Remo</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="fondo-modal-oscuro">
    <div class="modal-overlay">
        <main class="tarjeta-modal-confirmacion">
            <div class="cabecera-verde-modal">
                <h3>Confirmar entrega</h3>
                <a href="{{ route('historial') }}" class="btn-cerrar-x" aria-label="Volver al historial"><i class="fas fa-times"></i></a>
            </div>

            <div class="cuerpo-modal-confirmacion">
                <p><strong>Estado actual:</strong> <span class="badge pendiente">{{ $paquete->estado }}</span></p>
                <div class="info-entrega-grid">
                    <span class="info-label">Casa:</span>
                    <span class="info-valor">{{ $paquete->casa?->codigo ?? 'Casa no registrada' }}</span>
                    <span class="info-label">Destinatario:</span>
                    <span class="info-valor">{{ $paquete->destinatario ?: ($paquete->residente?->nombre ?? 'Sin destinatario registrado') }}</span>
                    <span class="info-label">Mensajería:</span>
                    <span class="info-valor">{{ $paquete->empresa }}</span>
                    <span class="info-label">Guía:</span>
                    <span class="info-valor">{{ $paquete->guia ?: 'Sin guía' }}</span>
                    <span class="info-label">Fecha:</span>
                    <span class="info-valor">{{ $paquete->created_at->format('d/m/Y - H:i') }}</span>
                </div>

                @if ($paquete->autorizacionActiva)
                    <div class="alerta-exito" style="margin-top: 18px;">
                        <strong>Este paquete tiene un tercero autorizado.</strong><br>
                        Nombre: {{ $paquete->autorizacionActiva->nombre_autorizado }}<br>
                        Documento: {{ $paquete->autorizacionActiva->documento_autorizado }}<br>
                        Relación: {{ $paquete->autorizacionActiva->relacion }}
                    </div>
                @endif

                @if ($codigoManual)
                    <div class="alerta-error" role="status" style="margin-top: 16px;">
                        @if ($estadoWhatsApp === 'invalid_phone')
                            WhatsApp no se envió porque la casa no tiene un teléfono de residente válido en formato internacional.
                        @elseif ($estadoWhatsApp === 'failed')
                            El proveedor no confirmó el envío por WhatsApp.
                        @else
                            WhatsApp no está configurado; no se realizó ningún envío.
                        @endif
                        Comunica este código al residente por un canal seguro: <strong>{{ $codigoManual }}</strong>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alerta-exito" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alerta-error" role="alert">{{ session('error') }}</div>
                @endif

                @if (! $codigoValidado)
                    <form action="{{ route('entrega.validar', $paquete) }}" method="post" style="margin-top: 20px;">
                        @csrf
                        <div class="grupo-input">
                            <label for="codigo_entrega">Código único de entrega</label>
                            <input id="codigo_entrega" name="codigo_entrega" type="text" maxlength="10" autocomplete="off" required value="{{ old('codigo_entrega') }}" aria-describedby="codigo-ayuda">
                            <small id="codigo-ayuda">Verifica que el código corresponda a este paquete, su casa y su destinatario.</small>
                            @error('codigo_entrega')
                                <div class="alerta-error" role="alert">{{ $message }}</div>
                            @enderror
                            @error('entrega')
                                <div class="alerta-error" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn-confirmar-verde">Validar código</button>
                    </form>
                @else
                    <form action="{{ route('entrega.confirmar', $paquete) }}" method="post" style="margin-top: 20px;">
                        @csrf
                        <div class="alerta-exito" role="status">Código válido para este paquete. Confirma quién lo recibe.</div>
                        <div class="grupo-input">
                            <label>¿Quién recibe el paquete?</label>
                            <label style="font-weight: 400;">
                                <input type="radio" name="tipo_receptor" value="residente" @checked(old('tipo_receptor', 'residente') === 'residente') required>
                                Destinatario: {{ $paquete->destinatario ?: ($paquete->residente?->nombre ?? 'Destinatario registrado') }}
                            </label>
                            @if ($paquete->autorizacionActiva)
                                <label style="font-weight: 400;">
                                    <input type="radio" name="tipo_receptor" value="autorizado" @checked(old('tipo_receptor') === 'autorizado')>
                                    Persona autorizada: {{ $paquete->autorizacionActiva->nombre_autorizado }}
                                </label>
                            @endif
                            @error('tipo_receptor')
                                <div class="alerta-error" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn-confirmar-verde">Confirmar entrega</button>
                    </form>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
