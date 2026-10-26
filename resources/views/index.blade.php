<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valle de San Remo - Panel de Control</title>
    <link rel="stylesheet" href="/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="layout-principal">
        @include('partials.navegacion')

        <main class="zona-trabajo">
            <div class="encabezado-seccion">
                <h2>Control y Recepción de Paquetería</h2>
                <p>Ingrese los datos del paquete recibido en portería para notificar al residente.</p>
            </div>

            <section class="contenedor-formulario">
                <form class="formulario-paquete" action="{{ route('paquetes.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @if ($errors->any())
                        <div class="alerta-error" role="alert">Revisa los campos marcados antes de registrar el paquete.</div>
                    @endif
                    <div class="grupo-input">
                        <label for="casa_id"><i class="fas fa-house"></i> Código de casa</label>
                        <select id="casa_id" name="casa_id" required>
                            <option value="">Selecciona una casa</option>
                            @foreach ($casasParaFormulario as $casa)
                                <option value="{{ $casa['id'] }}" @selected((string) old('casa_id') === (string) $casa['id'])>{{ $casa['codigo'] }}</option>
                            @endforeach
                        </select>
                        @error('casa_id')
                            <span>{{ $message }}</span>
                        @enderror
                        <span id="aviso-residente" class="texto-ayuda" role="status" aria-live="polite"></span>
                    </div>

                    <div class="grupo-input">
                        <label for="destinatario"><i class="fas fa-user-tag"></i> Destinatario</label>
                        <input type="text" id="destinatario" name="destinatario" value="{{ old('destinatario') }}" maxlength="255" autocomplete="off" required>
                        @error('destinatario')<span>{{ $message }}</span>@enderror
                    </div>

                    <div class="grupo-input">
                        <label for="empresa"><i class="fas fa-truck"></i> Empresa de Mensajería</label>
                        <input type="text" id="empresa" name="empresa" value="{{ old('empresa') }}" placeholder="Ej: Servientrega, Coordinadora" required>
                        @error('empresa')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grupo-input">
                        <label for="guia"><i class="fas fa-barcode"></i> Número de guía / Tracking</label>
                        <input type="text" id="guia" name="guia" value="{{ old('guia') }}" placeholder="Ej. SER123456789" maxlength="100">
                        @error('guia')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grupo-input full-width">
                        <label for="foto"><i class="fas fa-camera"></i> Fotografía del paquete</label>
                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="ayuda-foto">
                        <span id="ayuda-foto" class="texto-ayuda">Opcional. Formatos permitidos: JPG, PNG o WEBP. Tamaño máximo: 5 MB.</span>
                        @error('foto')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grupo-input full-width">
                        <label for="detalles"><i class="fas fa-info-circle"></i> Detalles o Descripción</label>
                        <textarea id="detalles" name="detalles" placeholder="Ej: Caja mediana, sobre de manila, paquete frágil...">{{ old('detalles') }}</textarea>
                        @error('detalles')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="zona-botones full-width">
                        <button type="submit" class="btn-notificar">
                            <i class="fas fa-bell"></i> Registrar y notificar al residente
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
    <script>
        const casaSelect = document.getElementById('casa_id');
        const destinatarioInput = document.getElementById('destinatario');
        const avisoResidente = document.getElementById('aviso-residente');
        const casas = @json($casasParaFormulario);
        const huboDestinatarioAnterior = @json(old('destinatario') !== null);

        function sincronizarDestinatario(cambioDeCasa = false) {
            const casa = casas.find((opcion) => String(opcion.id) === casaSelect.value);
            if (!casa) {
                if (cambioDeCasa) {
                    destinatarioInput.value = '';
                }
                avisoResidente.textContent = '';
                return;
            }

            if (cambioDeCasa || (!huboDestinatarioAnterior && destinatarioInput.value === '')) {
                destinatarioInput.value = casa.destinatario;
            }

            avisoResidente.textContent = casa.tieneResidenteUnico
                ? `Residente asociado: ${casa.destinatario}. Puedes cambiar el destinatario si el paquete es para otra persona de esta casa.`
                : 'Esta casa no tiene un residente principal único registrado. Escribe manualmente el nombre del destinatario.';
        }

        casaSelect.addEventListener('change', () => sincronizarDestinatario(true));
        sincronizarDestinatario();
    </script>
</body>
</html>
