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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('rol');
        });

        Schema::table('residentes', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('correo');
        });

        Schema::table('apartamentos', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('residentes', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
