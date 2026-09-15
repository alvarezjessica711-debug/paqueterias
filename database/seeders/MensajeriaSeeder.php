<?php

namespace Database\Seeders;

use App\Models\Mensajeria;
use Illuminate\Database\Seeder;

class MensajeriaSeeder extends Seeder
{
    public function run(): void
    {
        Mensajeria::query()->create([
            'nombre' => 'Servientrega',
            'tipo_servicio' => 'Paquetería y Sobres',
            'linea_atencion' => '01 8000 123 456',
            'email' => 'contacto@servientrega.com.co',
            'sitio_web' => 'https://www.servientrega.com.co',
        ]);

        Mensajeria::query()->create([
            'nombre' => 'Coordinadora',
            'tipo_servicio' => 'Cajas y Mercancía',
            'linea_atencion' => '(601) 486 8080',
            'email' => 'info@coordinadora.com.co',
            'sitio_web' => 'https://www.coordinadora.com.co',
        ]);

        Mensajeria::query()->create([
            'nombre' => 'Envía',
            'tipo_servicio' => 'Documentos y Paquetes',
            'linea_atencion' => '01 8000 913 000',
            'email' => 'contacto@envia.com.co',
            'sitio_web' => 'https://www.envia.com.co',
        ]);

        Mensajeria::query()->create([
            'nombre' => 'Interrapidísimo',
            'tipo_servicio' => 'Carga Pesada',
            'linea_atencion' => '(601) 560 5000',
            'email' => 'info@interrapidisimo.com.co',
            'sitio_web' => 'https://www.interrapidisimo.com.co',
        ]);
    }
}
