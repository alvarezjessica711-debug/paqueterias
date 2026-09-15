<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a package uses an apartment associated with the selected resident', function () {
    $user = User::factory()->create();
    $residente = Residente::factory()->create(['nombre' => 'Laura Gomez']);
    $primerApartamento = Apartamento::factory()->create(['torre' => 'Torre 2', 'numero' => '105']);
    $segundoApartamento = Apartamento::factory()->create(['torre' => 'Torre 1', 'numero' => '402']);
    $residente->apartamentos()->attach([$primerApartamento->id, $segundoApartamento->id]);

    $this->actingAs($user)
        ->get(route('registro-paquetes'))
        ->assertOk()
        ->assertSee('residente_busqueda')
        ->assertSee('residentes_disponibles')
        ->assertSee('apartamento_id')
        ->assertDontSee('name="torre"');

    $this->actingAs($user)
        ->post(route('paquetes.store'), [
            'residente_id' => $residente->id,
            'apartamento_id' => $segundoApartamento->id,
            'empresa' => 'Servientrega',
            'guia' => 'SER123456789',
            'detalles' => 'Caja mediana',
        ])
        ->assertRedirect(route('confirmacion'));

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => $residente->id,
        'apartamento_id' => $segundoApartamento->id,
        'empresa' => 'Servientrega',
        'guia' => 'SER123456789',
        'estado' => 'Pendiente',
    ]);
});

test('a guard can register a package with a photo stored on the public disk', function () {
    Storage::fake('public');

    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);

    $this->actingAs($vigilante)
        ->post(route('paquetes.store'), [
            'residente_id' => $residente->id,
            'apartamento_id' => $apartamento->id,
            'empresa' => 'Coordinadora',
            'guia' => 'COOR-2026-001',
            'detalles' => 'Caja con fotografía',
            'foto' => UploadedFile::fake()->image('paquete.png'),
        ])
        ->assertRedirect(route('confirmacion'));

    $paquete = Paquete::query()->sole();

    expect($paquete->estado)->toBe('Pendiente')
        ->and($paquete->guia)->toBe('COOR-2026-001')
        ->and($paquete->foto)->toStartWith('paquetes/');

    Storage::disk('public')->assertExists($paquete->foto);
});

test('a package rejects an invalid uploaded file', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);

    $this->actingAs($vigilante)
        ->from(route('registro-paquetes'))
        ->post(route('paquetes.store'), [
            'residente_id' => $residente->id,
            'apartamento_id' => $apartamento->id,
            'empresa' => 'Servientrega',
            'guia' => 'SER-INVALIDO',
            'foto' => UploadedFile::fake()->create('archivo.exe', 100, 'application/x-msdownload'),
        ])
        ->assertRedirect(route('registro-paquetes'))
        ->assertSessionHasErrors('foto');

    expect(Paquete::query()->count())->toBe(0);
});

test('a package cannot use an apartment from another resident', function () {
    $user = User::factory()->create();
    $residente = Residente::factory()->create();
    $apartamentoAjeno = Apartamento::factory()->create();

    $this->actingAs($user)
        ->from(route('registro-paquetes'))
        ->post(route('paquetes.store'), [
            'residente_id' => $residente->id,
            'apartamento_id' => $apartamentoAjeno->id,
            'empresa' => 'Servientrega',
            'guia' => 'SER-RELACION-INVALIDA',
        ])
        ->assertRedirect(route('registro-paquetes'))
        ->assertSessionHasErrors('apartamento_id');

    expect(Paquete::query()->count())->toBe(0);
});
