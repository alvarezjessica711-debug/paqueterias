<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->string('correo')->nullable()->after('telefono');
        });

        foreach (DB::table('residentes')->orderBy('id')->get() as $residente) {
            $apartamentoId = DB::table('apartamentos')
                ->where('torre', $residente->torre)
                ->where('numero', $residente->apartamento)
                ->value('id');

            if ($apartamentoId === null) {
                $apartamentoId = DB::table('apartamentos')->insertGetId([
                    'torre' => $residente->torre,
                    'numero' => $residente->apartamento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('apartamento_residente')->insertOrIgnore([
                'apartamento_id' => $apartamentoId,
                'residente_id' => $residente->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('paquetes')
                ->where('residente_id', $residente->id)
                ->whereNull('apartamento_id')
                ->update(['apartamento_id' => $apartamentoId]);
        }

        foreach (DB::table('paquetes')->whereNull('apartamento_id')->get() as $paquete) {
            $apartamentoId = DB::table('apartamentos')
                ->where('torre', $paquete->torre)
                ->where('numero', $paquete->apartamento)
                ->value('id');

            if ($apartamentoId === null) {
                $apartamentoId = DB::table('apartamentos')->insertGetId([
                    'torre' => $paquete->torre,
                    'numero' => $paquete->apartamento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('paquetes')->where('id', $paquete->id)->update(['apartamento_id' => $apartamentoId]);
        }

        Schema::table('residentes', function (Blueprint $table) {
            $table->dropUnique('residentes_torre_apartamento_unique');
            $table->dropColumn(['torre', 'apartamento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->string('torre')->nullable();
            $table->string('apartamento')->nullable();
            $table->dropColumn('correo');
        });
    }
};
