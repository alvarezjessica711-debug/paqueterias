<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nombres completos necesitan más espacio; conserva nulabilidad y los datos actuales.
        Schema::table('paquetes', function (Blueprint $table): void {
            $table->string('destinatario', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        $hayDestinatariosLargos = false;

        DB::table('paquetes')->select('id', 'destinatario')->whereNotNull('destinatario')->orderBy('id')->chunk(1000, function ($paquetes) use (&$hayDestinatariosLargos): void {
            foreach ($paquetes as $paquete) {
                if (mb_strlen($paquete->destinatario) > 10) {
                    $hayDestinatariosLargos = true;

                    return;
                }
            }
        });

        if ($hayDestinatariosLargos) {
            throw new RuntimeException('No se puede revertir: hay destinatarios de más de 10 caracteres.');
        }

        Schema::table('paquetes', function (Blueprint $table): void {
            $table->string('destinatario', 10)->nullable()->change();
        });
    }
};
