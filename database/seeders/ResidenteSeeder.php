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
            ['nombre' => 'María Fernanda López', 'casas' => ['C1'], 'telefono' => '3101234567'],
            ['nombre' => 'Juan Carlos Pérez', 'casas' => ['A1'], 'telefono' => '3209876543'],
            ['nombre' => 'Laura Gómez', 'casas' => ['B1', 'A2'], 'telefono' => '3004567890'],
            ['nombre' => 'Andrés Ramírez', 'casas' => ['A2'], 'telefono' => '3112223344'],
            ['nombre' => 'Camila Torres', 'casas' => ['B2'], 'telefono' => '3156549870'],
        ] as $residente) {
            $persona = Residente::query()->updateOrCreate(
                ['nombre' => $residente['nombre']],
                ['telefono' => $residente['telefono']],
            );

            foreach ($residente['casas'] as $codigo) {
                $casa = Apartamento::query()->firstOrCreate(['codigo' => $codigo]);
                $persona->casas()->syncWithoutDetaching([$casa->id]);
            }
        }
    }
}
