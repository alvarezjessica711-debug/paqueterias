<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearPaqueteHistorial(Residente $residente, Apartamento $apartamento, array $atributos = []): Paquete
{
    return Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'guia' => 'GUIA-HISTORIAL-'.fake()->unique()->numerify('####'),
        'estado' => 'Pendiente',
        ...$atributos,
    ]);
}

test('an administrator can consult all packages and filter history by status, dates and search terms', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $residente = Residente::factory()->create(['nombre' => 'Laura Gómez']);
    $apartamento = Apartamento::factory()->create(['torre' => 'Torre 2', 'numero' => '204']);
    $residente->apartamentos()->attach($apartamento);

    $pendiente = crearPaqueteHistorial($residente, $apartamento, ['guia' => 'GUIA-PENDIENTE-001']);
    $entregado = crearPaqueteHistorial($residente, $apartamento, [
        'guia' => 'GUIA-ENTREGADA-001',
        'empresa' => 'Coordinadora',
        'estado' => 'Entregado',
        'fecha_entrega' => now(),
        'recibido_por' => 'Laura Gómez',
    ]);
    $antiguo = crearPaqueteHistorial($residente, $apartamento, ['guia' => 'GUIA-ANTIGUA-001']);
    Paquete::query()->whereKey($antiguo)->update(['created_at' => now()->subMonth(), 'updated_at' => now()->subMonth()]);

    $this->actingAs($admin)
        ->get(route('historial'))
        ->assertOk()
        ->assertSeeText($pendiente->guia)
        ->assertSeeText($entregado->guia);

    $this->actingAs($admin)
        ->get(route('historial', ['estado' => 'Pendiente']))
        ->assertOk()
        ->assertSeeText($pendiente->guia)
        ->assertDontSeeText($entregado->guia);

    $this->actingAs($admin)
        ->get(route('historial', ['estado' => 'Entregado', 'fecha_desde' => now()->toDateString(), 'fecha_hasta' => now()->toDateString()]))
        ->assertOk()
        ->assertSeeText($entregado->guia)
        ->assertDontSeeText($antiguo->guia);

    $this->actingAs($admin)
        ->get(route('historial', ['busqueda' => 'GUIA-ENTREGADA-001']))
        ->assertOk()
        ->assertSeeText($entregado->guia)
        ->assertDontSeeText($pendiente->guia);

    $this->actingAs($admin)
        ->get(route('historial', ['residente' => 'Laura', 'apartamento' => '204']))
        ->assertOk()
        ->assertSeeText('Laura Gómez')
        ->assertSeeText('Torre 2');
});

test('administrator history pagination keeps active filters', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);

    foreach (range(1, 16) as $numero) {
        crearPaqueteHistorial($residente, $apartamento, ['guia' => "PAGINADA-{$numero}"]);
    }

    $this->actingAs($admin)
        ->get(route('historial', ['estado' => 'Pendiente']))
        ->assertOk()
        ->assertSee('Página 1 de 2')
        ->assertSee('estado=Pendiente', false);
});

test('a resident filters only their own pending, delivered and previous-month packages', function () {
    $residente = Residente::factory()->create(['nombre' => 'María López']);
    $otroResidente = Residente::factory()->create(['nombre' => 'Otra Persona']);
    $apartamento = Apartamento::factory()->create();
    $otroApartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $otroResidente->apartamentos()->attach($otroApartamento);
    $usuario = User::factory()->create(['rol' => 'residente', 'residente_id' => $residente->id]);

    $pendiente = crearPaqueteHistorial($residente, $apartamento, ['guia' => 'PROPIO-PENDIENTE']);
    $entregado = crearPaqueteHistorial($residente, $apartamento, [
        'guia' => 'PROPIO-ENTREGADO',
        'estado' => 'Entregado',
        'fecha_entrega' => now(),
        'recibido_por' => 'Pedro Pérez',
    ]);
    $anterior = crearPaqueteHistorial($residente, $apartamento, ['guia' => 'PROPIO-ANTERIOR']);
    Paquete::query()->whereKey($anterior)->update(['created_at' => now()->subMonth(), 'updated_at' => now()->subMonth()]);
    $ajeno = crearPaqueteHistorial($otroResidente, $otroApartamento, ['guia' => 'AJENO-NUNCA-VISIBLE']);

    $this->actingAs($usuario)
        ->get(route('mis-paquetes', ['estado' => 'Pendiente', 'mes' => now()->format('Y-m')]))
        ->assertOk()
        ->assertSeeText($pendiente->guia)
        ->assertDontSeeText($entregado->guia)
        ->assertDontSeeText($ajeno->guia);

    $this->actingAs($usuario)
        ->get(route('mis-paquetes', ['estado' => 'Entregado', 'mes' => now()->format('Y-m')]))
        ->assertOk()
        ->assertSeeText($entregado->guia)
        ->assertSeeText('Recibido por: Pedro Pérez')
        ->assertDontSeeText($ajeno->guia);

    $this->actingAs($usuario)
        ->get(route('mis-paquetes', ['mes' => now()->subMonth()->format('Y-m')]))
        ->assertOk()
        ->assertSeeText($anterior->guia)
        ->assertDontSeeText($ajeno->guia);
});
