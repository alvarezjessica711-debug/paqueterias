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
        Schema::create('apartamento_residente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['apartamento_id', 'residente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_residente');
    }
};
