<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valle de San Remo - Panel de Control</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
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
                    <div class="grupo-input full-width">
                        <label for="residente_busqueda"><i class="fas fa-user"></i> Residente</label>
                        <input id="residente_busqueda" list="residentes_disponibles" type="search" placeholder="Escriba un nombre, por ejemplo Laura" autocomplete="off" required>
                        <input id="residente_id" name="residente_id" type="hidden" value="{{ old('residente_id') }}">
                        <datalist id="residentes_disponibles">
                            @foreach ($residentes as $residente)
                                <option value="{{ $residente->nombre }} (ID #{{ $residente->id }})" label="{{ $residente->apartamentos->count() }} apartamento(s) activo(s)"></option>
                            @endforeach
                        </datalist>
                        @error('residente_id')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grupo-input">
                        <label for="apartamento_id"><i class="fas fa-door-closed"></i> Apartamento de entrega</label>
                        <select id="apartamento_id" name="apartamento_id" required disabled>
                            <option value="">Seleccione primero un residente</option>
                        </select>
                        @error('apartamento_id')
                            <span>{{ $message }}</span>
                        @enderror
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
                        <input type="text" id="guia" name="guia" value="{{ old('guia') }}" placeholder="Ej. SER123456789" maxlength="100" required>
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
        const residenteBusqueda = document.getElementById('residente_busqueda');
        const residenteId = document.getElementById('residente_id');
        const apartamentoSelect = document.getElementById('apartamento_id');
        const apartamentoSeleccionado = @json(old('apartamento_id'));
        const residentes = @json($residentesParaFormulario);

        function cargarApartamentos() {
            const residente = residentes.find((opcion) => opcion.etiqueta === residenteBusqueda.value.trim());
            const apartamentos = residente?.apartamentos ?? [];

            residenteId.value = residente?.id ?? '';
            const mensaje = residente === undefined
                ? 'Seleccione primero un residente'
                : apartamentos.length === 0
                    ? 'El residente no tiene apartamentos activos'
                    : 'Seleccione un apartamento';
            apartamentoSelect.replaceChildren(new Option(mensaje, ''));
            apartamentoSelect.disabled = apartamentos.length === 0;

            apartamentos.forEach((apartamento) => {
                const option = new Option(apartamento.texto, apartamento.id);
                option.selected = String(apartamento.id) === String(apartamentoSeleccionado);
                apartamentoSelect.add(option);
            });
        }

        const residenteAnterior = residentes.find((residente) => String(residente.id) === residenteId.value);

        if (residenteAnterior) {
            residenteBusqueda.value = residenteAnterior.etiqueta;
        }

        residenteBusqueda.addEventListener('input', cargarApartamentos);
        residenteBusqueda.addEventListener('change', cargarApartamentos);
        cargarApartamentos();
    </script>
</body>
</html>
