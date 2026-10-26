<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manual recipient without a resident link is shown in package, consultation, dashboard and history tables', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::query()->where('codigo', 'B5')->firstOrFail();
    $paquete = Paquete::query()->create([
        'residente_id' => null,
        'apartamento_id' => $casa->id,
        'empresa' => 'Mensajería de prueba',
        'destinatario' => 'Destinataria manual B5',
        'direccion' => $casa->codigo,
        'guia' => 'GUIA-DESTINATARIA-MANUAL',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($vigilante)
        ->get(route('paquetes'))
        ->assertOk()
        ->assertSeeText('Destinatario')
        ->assertSeeText('Destinataria manual B5')
        ->assertDontSeeText('Sin residente asignado');

    $this->actingAs($vigilante)
        ->get(route('consultar', ['busqueda' => 'Destinataria manual B5']))
        ->assertOk()
        ->assertSeeText('GUIA-DESTINATARIA-MANUAL')
        ->assertSeeText('Destinataria manual B5')
        ->assertDontSeeText('Sin residente');

    $this->actingAs($vigilante)
        ->get(route('historial', ['busqueda' => 'Destinataria manual B5']))
        ->assertOk()
        ->assertSeeText('Destinataria')
        ->assertSeeText('Destinataria manual B5')
        ->assertDontSeeText('Sin residente asignado');

    $this->actingAs($vigilante)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Destinataria manual B5');

    $this->assertDatabaseHas('paquetes', [
        'id' => $paquete->id,
        'residente_id' => null,
        'destinatario' => 'Destinataria manual B5',
    ]);
});
