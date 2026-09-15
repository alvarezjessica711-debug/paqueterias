<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a resident sees only their own packages in the personal views', function () {
    $residente = Residente::factory()->create(['nombre' => 'María Fernanda López']);
    $otroResidente = Residente::factory()->create(['nombre' => 'Otro Residente']);
    $apartamento = Apartamento::factory()->create();
    $otroApartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $otroResidente->apartamentos()->attach($otroApartamento);
    $user = User::factory()->create([
        'rol' => 'residente',
        'residente_id' => $residente->id,
    ]);

    Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'guia' => 'GUIA-PROPIA-001',
        'detalles' => 'Paquete propio',
        'estado' => 'Entregado',
        'fecha_entrega' => now(),
        'recibido_por' => 'María Fernanda López',
    ]);
    Paquete::query()->create([
        'residente_id' => $otroResidente->id,
        'apartamento_id' => $otroApartamento->id,
        'empresa' => 'Coordinadora',
        'guia' => 'GUIA-AJENA-001',
        'detalles' => 'Paquete ajeno',
        'estado' => 'Entregado',
    ]);

    $this->actingAs($user)
        ->get(route('mis-paquetes'))
        ->assertOk()
        ->assertSeeText('Paquete propio')
        ->assertSeeText('GUIA-PROPIA-001')
        ->assertSeeText('Recibido por: María Fernanda López')
        ->assertDontSeeText('GUIA-AJENA-001')
        ->assertDontSeeText('Paquete ajeno');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Mis paquetes')
        ->assertDontSeeText('Registrar Paquete')
        ->assertDontSeeText('Configuración');

    $this->actingAs($user)
        ->get(route('historial'))
        ->assertOk()
        ->assertSeeText('Paquete propio')
        ->assertDontSeeText('Paquete ajeno');
});

test('a resident cannot access operational or administrative actions', function () {
    $user = User::factory()->create(['rol' => 'residente']);

    $this->actingAs($user)->get(route('registro-paquetes'))->assertForbidden();
    $this->actingAs($user)->get(route('consultar'))->assertForbidden();
    $this->actingAs($user)->get(route('entrega'))->assertForbidden();
    $this->actingAs($user)->get(route('residentes'))->assertForbidden();
    $this->actingAs($user)->get(route('configuracion'))->assertForbidden();
    $this->actingAs($user)->get(route('paquetes'))->assertForbidden();
    $this->actingAs($user)->post(route('paquetes.store'), [])->assertForbidden();
});
