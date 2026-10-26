<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the dashboard displays the resident assigned to a package', function () {
    $residente = Residente::factory()->create([
        'nombre' => 'María Fernanda López',
    ]);
    $user = User::factory()->create([
        'rol' => 'residente',
        'residente_id' => $residente->id,
    ]);
    $apartamento = Apartamento::factory()->create(['codigo' => 'C10']);
    $residente->apartamentos()->attach($apartamento);

    Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'detalles' => 'Caja mediana',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Hola, '.$user->name)
        ->assertSeeText('Residente')
        ->assertSeeText('María Fernanda López')
        ->assertSeeText('C10');
});
