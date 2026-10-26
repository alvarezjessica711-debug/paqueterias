<?php

namespace Database\Seeders;

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaquetesConsultaDemoSeeder extends Seeder
{
    /**
     * Seed optional fake packages for local search testing.
     * Run explicitly with: php artisan db:seed --class=PaquetesConsultaDemoSeeder
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los datos de consulta de demostración solo se pueden cargar en local o testing.');
        }

        DB::transaction(function (): void {
            // The catalogue migration provides these records. Validate rather
            // than create or rewrite houses so existing links stay untouched.
            foreach (range('A', 'F') as $manzana) {
                foreach (range(1, 5) as $numero) {
                    $codigo = $manzana.$numero;
                    if (! DB::table('apartamentos')->where('codigo', $codigo)->exists()) {
                        throw new RuntimeException("No existe la casa {$codigo}; ejecuta primero las migraciones pendientes.");
                    }
                }
            }

            $residentes = [
                'A1' => ['Valentina Ríos', 'Laura Méndez'],
                'A2' => ['Santiago Pardo'],
                'B1' => ['Bruno Salazar'],
                'B2' => ['Juliana Acosta'],
                'C1' => ['Nora Castillo'],
                'C2' => ['Tomás Herrera'],
                'D1' => ['Sara Beltrán'],
                'D2' => ['Nicolás Vega'],
                'E1' => ['Gabriela Ortiz'],
                'E2' => ['Mateo Cárdenas'],
                'F1' => ['Isabella Cruz'],
                'F2' => ['Samuel Rojas'],
            ];

            foreach ($residentes as $codigo => $nombres) {
                $casa = Apartamento::query()->where('codigo', $codigo)->firstOrFail();
                foreach ($nombres as $nombre) {
                    $residente = Residente::query()->firstOrCreate(['nombre' => $nombre], ['activo' => true]);
                    $residente->casas()->syncWithoutDetaching([$casa->id]);
                }
            }

            $paquetesDemo = [
                ['DEMO-CONSULTA-A1-001', 'A1', 'Valentina Ríos', 'Coordinadora', 'Pendiente', '2026-09-01 09:15:00'],
                ['DEMO-CONSULTA-A1-002', 'A1', 'Laura Méndez', 'Servientrega', 'Entregado', '2026-09-02 10:30:00'],
                ['DEMO-CONSULTA-A2-001', 'A2', 'Santiago Pardo', 'Inter Rapidísimo', 'Pendiente', '2026-09-03 11:45:00'],
                ['DEMO-CONSULTA-B1-001', 'B1', 'Bruno Salazar', 'Envía', 'Pendiente', '2026-09-04 08:20:00'],
                ['DEMO-CONSULTA-B1-002', 'B1', 'Bruno Salazar', 'TCC', 'Entregado', '2026-09-05 13:00:00'],
                ['DEMO-CONSULTA-B2-001', 'B2', 'Juliana Acosta', 'DHL', 'Pendiente', '2026-09-06 14:10:00'],
                ['DEMO-CONSULTA-C1-001', 'C1', 'Nora Castillo', 'Coordinadora', 'Pendiente', '2026-09-07 09:40:00'],
                ['DEMO-CONSULTA-C1-002', 'C1', 'Nora Castillo', 'FedEx', 'Entregado', '2026-09-08 16:25:00'],
                ['DEMO-CONSULTA-C2-001', 'C2', 'Tomás Herrera', 'Servientrega', 'Pendiente', '2026-09-09 12:05:00'],
                ['DEMO-CONSULTA-D1-001', 'D1', 'Sara Beltrán', 'Mercado Libre', 'Entregado', '2026-09-10 10:00:00'],
                ['DEMO-CONSULTA-D2-001', 'D2', 'Nicolás Vega', 'Coordinadora', 'Pendiente', '2026-09-11 15:30:00'],
                ['DEMO-CONSULTA-E1-001', 'E1', 'Gabriela Ortiz', 'TCC', 'Pendiente', '2026-09-12 08:45:00'],
                ['DEMO-CONSULTA-E2-001', 'E2', 'Mateo Cárdenas', 'Envía', 'Entregado', '2026-09-13 11:10:00'],
                ['DEMO-CONSULTA-F1-001', 'F1', 'Isabella Cruz', 'DHL', 'Pendiente', '2026-09-14 13:55:00'],
                ['DEMO-CONSULTA-F2-001', 'F2', 'Samuel Rojas', 'Inter Rapidísimo', 'Pendiente', '2026-09-15 09:25:00'],
            ];

            $creados = 0;
            $vigilanteId = DB::table('users')->where('rol', 'vigilante')->where('activo', true)->value('id');
            foreach ($paquetesDemo as [$guia, $codigo, $nombre, $empresa, $estado, $fecha]) {
                if (Paquete::query()->where('guia', $guia)->exists()) {
                    continue;
                }

                $residente = Residente::query()->where('nombre', $nombre)->firstOrFail();
                $casa = $residente->casas()->where('codigo', $codigo)->firstOrFail();
                $entregado = $estado === 'Entregado';

                DB::table('paquetes')->insert([
                    'residente_id' => $residente->id,
                    'apartamento_id' => $casa->id,
                    'empresa' => $empresa,
                    'destinatario' => $nombre,
                    'direccion' => $codigo,
                    'guia' => $guia,
                    'detalles' => 'Dato ficticio para probar consultas; no es un registro real de entrega.',
                    'estado' => $estado,
                    'fecha_entrega' => $entregado ? $fecha : null,
                    'recibido_por' => $entregado ? $nombre : null,
                    'entregado_por' => $entregado ? $vigilanteId : null,
                    'firma' => null,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);
                $creados++;
            }

            $this->command?->info("Paquetes ficticios de consulta creados: {$creados}; existentes con estas guías se dejaron intactos.");
        });
    }
}
