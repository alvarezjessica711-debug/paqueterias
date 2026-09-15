<?php

namespace Database\Seeders;

use App\Models\Apartamento;
use App\Models\Residente;
use Illuminate\Database\Seeder;

class ResidenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['nombre' => 'María Fernanda López', 'apartamentos' => [['torre' => 'Torre 2', 'numero' => '204']], 'telefono' => '3101234567'],
            ['nombre' => 'Juan Carlos Pérez', 'apartamentos' => [['torre' => 'Torre 1', 'numero' => '301']], 'telefono' => '3209876543'],
            ['nombre' => 'Laura Gómez', 'apartamentos' => [['torre' => 'Torre 2', 'numero' => '105'], ['torre' => 'Torre 1', 'numero' => '402']], 'telefono' => '3004567890'],
            ['nombre' => 'Andrés Ramírez', 'apartamentos' => [['torre' => 'Torre 1', 'numero' => '402']], 'telefono' => '3112223344'],
            ['nombre' => 'Camila Torres', 'apartamentos' => [['torre' => 'Torre 2', 'numero' => '203']], 'telefono' => '3156549870'],
        ] as $residente) {
            $persona = Residente::query()->updateOrCreate(
                ['nombre' => $residente['nombre']],
                ['telefono' => $residente['telefono']],
            );

            foreach ($residente['apartamentos'] as $datosApartamento) {
                $apartamento = Apartamento::query()->firstOrCreate($datosApartamento);
                $persona->apartamentos()->syncWithoutDetaching([$apartamento->id]);
            }
        }
    }
}
