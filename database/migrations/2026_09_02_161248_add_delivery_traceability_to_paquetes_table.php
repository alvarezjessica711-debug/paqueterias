<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->foreignId('entregado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('residente_id');
            $table->timestamp('fecha_entrega')->nullable()->after('estado');
            $table->string('recibido_por')->nullable()->after('fecha_entrega');
            $table->text('firma')->nullable()->after('recibido_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entregado_por');
            $table->dropColumn(['fecha_entrega', 'recibido_por', 'firma']);
        });
    }
};
