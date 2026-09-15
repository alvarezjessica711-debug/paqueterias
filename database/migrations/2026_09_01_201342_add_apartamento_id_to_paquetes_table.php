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
            $table->foreignId('apartamento_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->after('residente_id');

            $table->string('torre')->nullable()->change();
            $table->string('apartamento')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apartamento_id');
            $table->string('torre')->nullable(false)->change();
            $table->string('apartamento')->nullable(false)->change();
        });
    }
};
