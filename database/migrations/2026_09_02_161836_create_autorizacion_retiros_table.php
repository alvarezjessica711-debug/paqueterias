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
        Schema::create('autorizaciones_retiro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paquete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->string('nombre_autorizado', 255);
            $table->string('documento_autorizado', 100);
            $table->string('relacion', 100);
            $table->text('observacion')->nullable();
            $table->string('estado', 20)->default('Activa');
            $table->timestamps();

            $table->index(['paquete_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autorizaciones_retiro');
    }
};
