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
        if (! Schema::hasTable('mensajerias') || ! Schema::hasColumn('mensajerias', 'estado')) {
            return;
        }

        Schema::table('mensajerias', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mensajerias') || Schema::hasColumn('mensajerias', 'estado')) {
            return;
        }

        Schema::table('mensajerias', function (Blueprint $table) {
            $table->enum('estado', ['Activa', 'Inactiva'])->default('Activa');
        });
    }
};
