<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();
        $casas = [];

        foreach (range('A', 'F') as $manzana) {
            foreach (range(1, 5) as $numero) {
                $casas[] = [
                    'codigo' => $manzana.$numero,
                    'ubicacion_anterior' => null,
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        DB::table('apartamentos')->insertOrIgnore($casas);
    }

    public function down(): void
    {
        // Catalog entries may have acquired residents, packages or changes.
        // Leave them intact during rollback to avoid deleting real data.
    }
};
