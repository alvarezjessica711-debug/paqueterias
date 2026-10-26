<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->string('codigo_entrega', 10)->nullable()->unique();
            $table->foreignId('codigo_entrega_validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('codigo_entrega_validado_at')->nullable();
            $table->timestamp('codigo_entrega_usado_en')->nullable();
            $table->timestamp('codigo_entrega_whatsapp_aceptado_at')->nullable();
        });

        // The signature is intentionally removed as part of replacing that collection mechanism.
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropColumn('firma');
        });
    }

    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropForeign(['codigo_entrega_validado_por']);
            $table->dropUnique(['codigo_entrega']);
            $table->dropColumn([
                'codigo_entrega',
                'codigo_entrega_validado_por',
                'codigo_entrega_validado_at',
                'codigo_entrega_usado_en',
                'codigo_entrega_whatsapp_aceptado_at',
            ]);
            $table->text('firma')->nullable();
        });
    }
};
