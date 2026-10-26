<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->string('destinatario', 10)->nullable();
            $table->string('direccion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropColumn(['destinatario', 'direccion']);
        });
    }
};
