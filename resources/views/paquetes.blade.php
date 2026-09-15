<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paquetes - Valle de San Remo</title>
    <link rel="stylesheet" href="{{ asset('estilos.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            background-image: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), 
                              url("https://www.construespacios.com/wp-content/uploads/2025/01/WhatsApp-Image-2024-03-13-at-2.06.15-AM-2.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 { color: white; text-shadow: 2px 4px 10px rgba(0,0,0,0.6); margin-bottom: 20px; text-align: center; }

        main {
            background-color: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            width: 95%;
            max-width: 600px;
        }

        h2 { color: #1a5276; text-align: center; margin-bottom: 20px; }

        /* Estilo de la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 15px;
            overflow: hidden;
        }

        th {
            background-color: #2980b9;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 0.9em;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: #2c3e50;
            font-size: 0.85em;
        }

        .estado-ok {
            color: #27ae60;
            font-weight: bold;
        }

        .btn-volver {
            display: block;
            margin-top: 25px;
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Tus Paquetes Pendientes</h1>

    <main>
        <h2>Detalle de Correspondencia</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Torre</th>
                    <th>Apartamento</th>
                    <th>Mensajería</th>
                    <th>Detalles</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paquetes as $paquete)
                    <tr>
                        <td>{{ $paquete->created_at->format('d/m/Y') }}</td>
                        <td>{{ $paquete->apartamento?->torre ?? 'Sin torre' }}</td>
                        <td>{{ $paquete->apartamento?->numero ?? 'Sin apartamento' }}</td>
                        <td>{{ $paquete->empresa }}</td>
                        <td>{{ $paquete->detalles ?? 'Sin detalles' }}</td>
                        <td class="estado-ok">{{ $paquete->estado }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay paquetes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        
    </main>

</body>
</html>
