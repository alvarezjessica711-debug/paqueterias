<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the dashboard displays recently registered packages', function () {
    $user = User::factory()->create();
    $apartamento = Apartamento::factory()->create(['torre' => 'Torre 1', 'numero' => '402']);

    Paquete::query()->create([
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'detalles' => 'Caja mediana',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Torre 1')
        ->assertSeeText('402')
        ->assertSeeText('Servientrega')
        ->assertSeeText('Pendiente')
        ->assertDontSeeText('María Fernanda López');
});
