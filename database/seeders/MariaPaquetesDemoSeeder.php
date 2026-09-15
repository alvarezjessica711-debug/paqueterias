<?php

namespace Database\Seeders;

use App\Models\Paquete;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MariaPaquetesDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $usuario = User::query()->where('email', 'maria.lopez@valledesanremo.test')->sole();
            $residente = $usuario->residente()->lockForUpdate()->firstOrFail();
            if ($usuario->rol !== 'residente' || ! $usuario->activo || ! $residente->activo || $residente->nombre !== 'María Fernanda López') {
                throw new RuntimeException('La cuenta no corresponde a la residente activa esperada.');
            }
            $apartamento = $residente->apartamentos()->where('activo', true)->where('torre', 'Torre 2')->where('numero', '204')->sole();
            $vigilante = User::query()->where('email', 'vigilante@valledesanremo.test')->where('rol', 'vigilante')->where('activo', true)->sole();

            $registros = [
                ['2026-02-05 09:00:00', 'Servientrega', '2026-02-05 17:00:00'],
                ['2026-02-20 10:00:00', 'Coordinadora', null],
                ['2026-03-10 08:30:00', 'Inter Rapidísimo', '2026-03-11 10:30:00'],
                ['2026-04-15 11:00:00', 'Envía', '2026-04-17 16:00:00'],
                ['2026-05-08 14:00:00', 'TCC', null],
                ['2026-06-12 09:00:00', 'DHL', '2026-06-15 18:00:00'],
                ['2026-07-21 10:00:00', 'FedEx', '2026-07-26 12:00:00'],
                ['2026-08-25 15:00:00', 'Mercado Libre', null],
                ['2026-09-03 08:00:00', 'Servientrega', '2026-09-03 15:00:00'],
                ['2026-09-07 10:00:00', 'Coordinadora', null],
                ['2026-09-08 14:00:00', 'Inter Rapidísimo', null],
            ];

            $firma = $this->firmaDemo();
            $creados = 0;
            foreach ($registros as $indice => [$recepcion, $empresa, $entrega]) {
                $guia = sprintf('DEMO-MARIA-2026-%03d', $indice + 1);
                $existente = Paquete::query()->where('guia', $guia)->first();
                if ($existente) {
                    if ($existente->residente_id !== $residente->id) {
                        throw new RuntimeException("La guía {$guia} pertenece a otro residente.");
                    }

                    continue;
                }

                // Inserción histórica sin eventos de modelo ni envío de notificaciones.
                DB::table('paquetes')->insert([
                    'residente_id' => $residente->id,
                    'apartamento_id' => $apartamento->id,
                    'empresa' => $empresa,
                    'guia' => $guia,
                    'detalles' => 'DEMO académica: registro histórico simulado; firma DEMO sin valor probatorio.',
                    'estado' => $entrega ? 'Entregado' : 'Pendiente',
                    'fecha_entrega' => $entrega,
                    'recibido_por' => $entrega ? $residente->nombre : null,
                    'entregado_por' => $entrega ? $vigilante->id : null,
                    'firma' => $entrega ? $firma : null,
                    'created_at' => $recepcion,
                    'updated_at' => $entrega ?? $recepcion,
                ]);
                $creados++;
            }
            $this->command?->info("Paquetes demo creados: {$creados}");
        });
    }

    private function firmaDemo(): string
    {
        $imagen = imagecreatetruecolor(240, 70);
        $fondo = imagecolorallocate($imagen, 255, 255, 255);
        $tinta = imagecolorallocate($imagen, 30, 30, 30);
        imagefill($imagen, 0, 0, $fondo);
        imagestring($imagen, 5, 20, 20, 'FIRMA DEMO', $tinta);
        imageline($imagen, 20, 45, 210, 45, $tinta);
        ob_start();
        imagepng($imagen);
        $png = ob_get_clean();
        imagedestroy($imagen);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
