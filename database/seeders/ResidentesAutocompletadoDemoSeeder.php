<?php

namespace Database\Seeders;

use App\Models\Apartamento;
use App\Models\Residente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResidentesAutocompletadoDemoSeeder extends Seeder
{
    /**
     * Add clearly marked fictional residents only to active catalogue houses
     * that currently have no active resident. Existing people and links stay untouched.
     * Run explicitly in local/testing with: php artisan db:seed --class=ResidentesAutocompletadoDemoSeeder
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los residentes ficticios solo se pueden cargar en local o testing.');
        }

        DB::transaction(function (): void {
            $creados = 0;
            foreach (range('A', 'F') as $manzana) {
                foreach (range(1, 5) as $numero) {
                    $codigo = $manzana.$numero;
                    $casa = Apartamento::query()
                        ->where('codigo', $codigo)
                        ->where('activo', true)
                        ->first();

                    if ($casa === null || $casa->residentes()->where('residentes.activo', true)->exists()) {
                        continue;
                    }

                    $nombre = "Demo ficticio {$codigo}";
                    // A prior or manually detached demo row is left untouched rather than reused or reassigned.
                    if (Residente::query()->where('nombre', $nombre)->exists()) {
                        continue;
                    }

                    $residente = Residente::query()->create([
                        'nombre' => $nombre,
                        'activo' => true,
                    ]);
                    $casa->residentes()->attach($residente->id);
                    $creados++;
                }
            }

            $this->command?->info("Residentes ficticios creados para autocompletado: {$creados}; casas con asociaciones existentes no se modificaron.");
        });
    }
}
