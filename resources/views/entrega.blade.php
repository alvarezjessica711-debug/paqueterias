<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma de Entrega - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="fondo-modal-oscuro">
    <div class="modal-overlay">
        <div class="tarjeta-modal-firma">
            <div class="cabecera-verde-modal">
                <h3>Confirmar Entrega</h3>
                <a href="{{ route('historial') }}" class="btn-cerrar-x" aria-label="Cerrar confirmación de entrega"><i class="fas fa-times"></i></a>
            </div>

            <form id="form-entrega" class="cuerpo-modal-firma" action="{{ route('entrega.confirmar', $paquete) }}" method="post">
                @csrf
                <input type="hidden" id="firma" name="firma">
                <div class="info-entrega-grid">
                    <span class="info-label">Apartamento:</span>
                    <span class="info-valor">{{ $paquete->apartamento?->numero ?? 'Sin apartamento' }} - {{ $paquete->apartamento?->torre ?? 'Sin torre' }}</span>

                    <span class="info-label">Residente:</span>
                    <span class="info-valor">{{ $paquete->residente?->nombre ?? 'Sin residente asignado' }}</span>

                    <span class="info-label">Mensajería:</span>
                    <span class="info-valor">{{ $paquete->empresa }}</span>

                    <span class="info-label">Fecha:</span>
                    <span class="info-valor">{{ $paquete->created_at->format('d/m/Y - H:i') }}</span>
                </div>

                @if ($paquete->autorizacionActiva)
                    <div class="alerta-exito" style="margin-top: 18px;">
                        <strong>Este paquete tiene un tercero autorizado.</strong><br>
                        Nombre: {{ $paquete->autorizacionActiva->nombre_autorizado }}<br>
                        Documento: {{ $paquete->autorizacionActiva->documento_autorizado }}<br>
                        Relación: {{ $paquete->autorizacionActiva->relacion }}
                        @if ($paquete->autorizacionActiva->observacion)
                            <br>Observación: {{ $paquete->autorizacionActiva->observacion }}
                        @endif
                    </div>
                @endif

                <div class="grupo-input" style="margin-top: 18px;">
                    <label>¿Quién recibe el paquete?</label>
                    <label style="font-weight: 400;"><input type="radio" name="tipo_receptor" value="residente" @checked(old('tipo_receptor', 'residente') === 'residente')> Entrega al residente: {{ $paquete->residente?->nombre }}</label>
                    @if ($paquete->autorizacionActiva)
                        <label style="font-weight: 400;"><input type="radio" name="tipo_receptor" value="autorizado" @checked(old('tipo_receptor') === 'autorizado')> Entrega a persona autorizada: {{ $paquete->autorizacionActiva->nombre_autorizado }}</label>
                    @endif
                    @error('tipo_receptor')
                        <div class="alerta-error">{{ $message }}</div>
                    @enderror
                </div>

                <p class="titulo-firma">Firma de quien recibe</p>

                <div class="caja-firma">
                    <canvas id="lienzo-firma" aria-label="Área para registrar la firma digital"></canvas>
                </div>

                @error('firma')
                    <div class="alerta-error">{{ $message }}</div>
                @enderror

                @error('entrega')
                    <div class="alerta-error">{{ $message }}</div>
                @enderror

                <button type="button" class="btn-limpiar-firma" id="btn-limpiar">Limpiar firma</button>

                <button type="submit" class="btn-confirmar-verde">Confirmar Entrega</button>
            </form>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('lienzo-firma');
        const ctx = canvas.getContext('2d');
        const btnLimpiar = document.getElementById('btn-limpiar');
        const formEntrega = document.getElementById('form-entrega');
        const firma = document.getElementById('firma');

        function ajustarCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
        }

        window.addEventListener('resize', ajustarCanvas);
        ajustarCanvas();

        let dibujando = false;
        let ultimaX = 0;
        let ultimaY = 0;

        function obtenerPosicion(evento) {
            const rect = canvas.getBoundingClientRect();
            let clienteX = evento.clientX;
            let clienteY = evento.clientY;

            if (evento.touches && evento.touches.length > 0) {
                clienteX = evento.touches[0].clientX;
                clienteY = evento.touches[0].clientY;
            }

            return {
                x: clienteX - rect.left,
                y: clienteY - rect.top,
            };
        }

        function iniciarDibujo(evento) {
            dibujando = true;
            const posicion = obtenerPosicion(evento);
            ultimaX = posicion.x;
            ultimaY = posicion.y;
        }

        function dibujar(evento) {
            if (!dibujando) return;

            evento.preventDefault();
            const posicion = obtenerPosicion(evento);

            ctx.beginPath();
            ctx.moveTo(ultimaX, ultimaY);
            ctx.lineTo(posicion.x, posicion.y);
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.stroke();

            ultimaX = posicion.x;
            ultimaY = posicion.y;
        }

        function detenerDibujo() {
            dibujando = false;
        }

        canvas.addEventListener('mousedown', iniciarDibujo);
        canvas.addEventListener('mousemove', dibujar);
        canvas.addEventListener('mouseup', detenerDibujo);
        canvas.addEventListener('mouseout', detenerDibujo);
        canvas.addEventListener('touchstart', iniciarDibujo, { passive: false });
        canvas.addEventListener('touchmove', dibujar, { passive: false });
        canvas.addEventListener('touchend', detenerDibujo);

        btnLimpiar.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            firma.value = '';
        });

        formEntrega.addEventListener('submit', (evento) => {
            const pixeles = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            const tieneTrazo = pixeles.some((valor, indice) => indice % 4 === 3 && valor !== 0);

            if (! tieneTrazo) {
                evento.preventDefault();
                alert('Debes registrar una firma antes de confirmar la entrega.');

                return;
            }

            firma.value = canvas.toDataURL('image/png');
        });
    </script>
</body>
</html>
