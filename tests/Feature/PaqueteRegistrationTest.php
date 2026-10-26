<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('package form starts with house code and has the requested field order', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::factory()->create(['codigo' => 'A20']);

    $response = $this->actingAs($vigilante)->get(route('registro-paquetes'));

    $response->assertOk()
        ->assertSee('casa_id')
        ->assertSee('Destinatario')
        ->assertSee('Empresa de Mensajería')
        ->assertSee('Número de guía / Tracking')
        ->assertSee('Fotografía del paquete')
        ->assertSee('Detalles o Descripción')
        ->assertSee('A20')
        ->assertDontSee('residente_busqueda')
        ->assertDontSee('name="residente_id"', false)
        ->assertDontSee('id="direccion"', false)
        ->assertDontSee('Seleccione primero un residente');

    $content = $response->getContent();
    expect(strpos($content, 'Código de casa'))
        ->toBeLessThan(strpos($content, '> Destinatario'))
        ->and(strpos($content, '> Destinatario'))
        ->toBeLessThan(strpos($content, 'Empresa de Mensajería'))
        ->and(strpos($content, 'Empresa de Mensajería'))
        ->toBeLessThan(strpos($content, 'Número de guía / Tracking'));
});

test('a guard can register a package from its house and the unique active resident is inferred', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'Laura Fernanda Gómez']);
    $residenteSuplantado = Residente::factory()->create();
    $casa = Apartamento::factory()->create(['codigo' => 'A99']);
    $residente->casas()->attach($casa);

    $this->actingAs($vigilante)
        ->post(route('paquetes.store'), [
            'casa_id' => $casa->id,
            'residente_id' => $residenteSuplantado->id,
            'empresa' => 'Servientrega',
            'guia' => 'SER123456789',
            'detalles' => 'Caja mediana',
        ])
        ->assertRedirect(route('confirmacion'));

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => $residente->id,
        'apartamento_id' => $casa->id,
        'empresa' => 'Servientrega',
        'destinatario' => 'Laura Fernanda Gómez',
        'direccion' => 'A99',
        'guia' => 'SER123456789',
        'estado' => 'Pendiente',
    ]);

    $paquete = Paquete::query()->sole();
    $this->assertDatabaseHas('paquete_auditorias', [
        'paquete_id' => $paquete->id,
        'campo' => 'destinatario',
        'valor_nuevo' => 'Laura Fernanda Gómez',
    ]);
});

test('a manually edited recipient keeps the house and does not assign another resident identity', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residentePrincipal = Residente::factory()->create(['nombre' => 'Camila Principal']);
    $residenteOtro = Residente::factory()->create(['nombre' => 'Persona Invitada']);
    $casa = Apartamento::factory()->create(['codigo' => 'B99']);
    $residentePrincipal->casas()->attach($casa);

    $this->actingAs($vigilante)->post(route('paquetes.store'), [
        'casa_id' => $casa->id,
        'destinatario' => 'Persona Invitada',
        'empresa' => 'Coordinadora',
    ])->assertRedirect(route('confirmacion'));

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => $residentePrincipal->id,
        'apartamento_id' => $casa->id,
        'destinatario' => 'Persona Invitada',
        'direccion' => 'B99',
    ]);
    $this->assertDatabaseMissing('paquetes', ['residente_id' => $residenteOtro->id]);
});

test('a house without one unique active resident allows a manually entered recipient', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::factory()->create(['codigo' => 'F99']);

    $this->actingAs($vigilante)->get(route('registro-paquetes'))
        ->assertOk()
        ->assertSee('Esta casa no tiene un residente principal único registrado. Escribe manualmente el nombre del destinatario.');

    $this->actingAs($vigilante)->post(route('paquetes.store'), [
        'casa_id' => $casa->id,
        'destinatario' => 'Visitante de F99',
        'empresa' => 'Mensajería Ejemplo',
    ])->assertRedirect(route('confirmacion'));

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => null,
        'apartamento_id' => $casa->id,
        'destinatario' => 'Visitante de F99',
        'direccion' => 'F99',
    ]);
});

test('a house with multiple active residents does not guess the recipient identity', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::factory()->create(['codigo' => 'C99']);
    Residente::factory()->create()->casas()->attach($casa);
    Residente::factory()->create()->casas()->attach($casa);

    $this->actingAs($vigilante)->post(route('paquetes.store'), [
        'casa_id' => $casa->id,
        'destinatario' => 'Destinatario de C1',
        'empresa' => 'Mensajería Ejemplo',
    ])->assertRedirect(route('confirmacion'));

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => null,
        'apartamento_id' => $casa->id,
        'destinatario' => 'Destinatario de C1',
    ]);
});

test('package registration requires a registered active house, company and recipient when not inferable', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casaInactiva = Apartamento::factory()->create(['activo' => false]);
    $casaVacia = Apartamento::factory()->create(['codigo' => 'F99']);

    $this->actingAs($vigilante)->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'casa_id' => 999999,
        'empresa' => 'Mensajería',
    ])->assertRedirect(route('registro-paquetes'))->assertSessionHasErrors('casa_id');

    $this->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'casa_id' => $casaInactiva->id,
        'empresa' => 'Mensajería',
        'destinatario' => 'Persona',
    ])->assertRedirect(route('registro-paquetes'))->assertSessionHasErrors('casa_id');

    $this->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'casa_id' => $casaVacia->id,
        'empresa' => '',
        'destinatario' => 'Persona',
    ])->assertRedirect(route('registro-paquetes'))->assertSessionHasErrors('empresa');

    $this->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'casa_id' => $casaVacia->id,
        'empresa' => 'Mensajería',
        'destinatario' => '',
    ])->assertRedirect(route('registro-paquetes'))->assertSessionHasErrors('destinatario');

    $this->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'casa_id' => $casaVacia->id,
        'empresa' => 'Mensajería',
        'destinatario' => str_repeat('x', 256),
    ])->assertRedirect(route('registro-paquetes'))->assertSessionHasErrors('destinatario');

    expect(Paquete::query()->count())->toBe(0);
});

test('a guard can register a package with an optional photo stored on the public disk', function () {
    Storage::fake('public');

    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::factory()->create();

    $this->actingAs($vigilante)
        ->post(route('paquetes.store'), [
            'casa_id' => $casa->id,
            'destinatario' => 'Destinatario manual',
            'empresa' => 'Coordinadora',
            'foto' => UploadedFile::fake()->image('paquete.png'),
        ])
        ->assertRedirect(route('confirmacion'));

    $paquete = Paquete::query()->sole();

    expect($paquete->estado)->toBe('Pendiente')
        ->and($paquete->guia)->toBeNull()
        ->and($paquete->foto)->toStartWith('paquetes/');

    Storage::disk('public')->assertExists($paquete->foto);
});

test('a package rejects an invalid uploaded file', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $casa = Apartamento::factory()->create();

    $this->actingAs($vigilante)
        ->from(route('registro-paquetes'))
        ->post(route('paquetes.store'), [
            'casa_id' => $casa->id,
            'destinatario' => 'Destinatario',
            'empresa' => 'Servientrega',
            'foto' => UploadedFile::fake()->create('archivo.exe', 100, 'application/x-msdownload'),
        ])
        ->assertRedirect(route('registro-paquetes'))
        ->assertSessionHasErrors('foto');

    expect(Paquete::query()->count())->toBe(0);
});
