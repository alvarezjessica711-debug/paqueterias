<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paqueteConsulta(Residente $residente, Apartamento $casa, string $guia, string $estado = 'Pendiente'): Paquete
{
    return Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $casa->id,
        'empresa' => 'Mensajería de prueba',
        'guia' => $guia,
        'estado' => $estado,
    ]);
}

test('vigilante can search pending packages by full house code, resident full name and tracking number', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residenteA = Residente::factory()->create(['nombre' => 'Valentina Ríos']);
    $residenteB = Residente::factory()->create(['nombre' => 'Bruno Salazar']);
    $casaA1 = Apartamento::query()->where('codigo', 'A1')->firstOrFail();
    $casaB1 = Apartamento::query()->where('codigo', 'B1')->firstOrFail();
    $residenteA->casas()->attach($casaA1);
    $residenteB->casas()->attach($casaB1);

    paqueteConsulta($residenteA, $casaA1, 'TRACK-A1-001');
    paqueteConsulta($residenteA, $casaA1, 'TRACK-A1-002');
    paqueteConsulta($residenteB, $casaB1, 'TRACK-B1-001');
    paqueteConsulta($residenteB, $casaB1, 'TRACK-DELIVERED', 'Entregado');

    $this->actingAs($vigilante)->get(route('consultar', ['busqueda' => 'A1']))
        ->assertOk()->assertSeeText('TRACK-A1-001')->assertSeeText('TRACK-A1-002')->assertDontSeeText('TRACK-B1-001');

    $this->actingAs($vigilante)->get(route('consultar', ['busqueda' => 'Valentina Ríos']))
        ->assertOk()->assertSeeText('TRACK-A1-001')->assertSeeText('TRACK-A1-002')->assertDontSeeText('TRACK-B1-001');

    $this->actingAs($vigilante)->get(route('consultar', ['busqueda' => 'B1']))
        ->assertOk()->assertSeeText('TRACK-B1-001')->assertDontSeeText('TRACK-DELIVERED')->assertDontSeeText('TRACK-A1-001');

    $this->actingAs($vigilante)->get(route('consultar', ['busqueda' => 'TRACK-A1-002']))
        ->assertOk()->assertSeeText('TRACK-A1-002')->assertDontSeeText('TRACK-A1-001');
});

test('consultar form submits with enter, clear returns initial list, and no match has an empty message', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'Amalia Prueba']);
    $casa = Apartamento::query()->where('codigo', 'F5')->firstOrFail();
    $residente->casas()->attach($casa);
    paqueteConsulta($residente, $casa, 'GUIA-F5-OK');

    $this->actingAs($vigilante)->get(route('consultar', ['busqueda' => 'NO-EXISTE']))
        ->assertOk()->assertSeeText('No se encontraron paquetes.')->assertDontSeeText('GUIA-F5-OK');

    $this->actingAs($vigilante)->get(route('consultar'))
        ->assertOk()->assertSeeText('Entregas pendientes')->assertSeeText('GUIA-F5-OK')
        ->assertSee('method="get"', false)->assertSee('type="search"', false)
        ->assertSee(route('consultar'), false);
});
