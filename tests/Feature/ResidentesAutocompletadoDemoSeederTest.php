<?php

use App\Models\Apartamento;
use App\Models\Residente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo resident seeder adds one marked resident only to houses without active residents and is idempotent', function () {
    $casaConResidente = Apartamento::query()->where('codigo', 'B5')->firstOrFail();
    $existente = Residente::factory()->create(['nombre' => 'Residente existente B5', 'telefono' => '3000000000']);
    $existente->casas()->attach($casaConResidente);
    $casaVacia = Apartamento::query()->where('codigo', 'F5')->firstOrFail();

    $this->seed(Database\Seeders\ResidentesAutocompletadoDemoSeeder::class);

    expect($casaConResidente->residentes()->where('residentes.activo', true)->count())->toBe(1)
        ->and($casaVacia->residentes()->where('residentes.activo', true)->count())->toBe(1)
        ->and($casaVacia->residentes()->where('residentes.activo', true)->first()->nombre)->toBe('Demo ficticio F5');
    $this->assertDatabaseHas('residentes', ['id' => $existente->id, 'nombre' => 'Residente existente B5', 'telefono' => '3000000000']);

    $cantidad = Residente::query()->count();
    $this->seed(Database\Seeders\ResidentesAutocompletadoDemoSeeder::class);
    expect(Residente::query()->count())->toBe($cantidad);
});
