<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearPaqueteReporte(array $atributos = []): Paquete
{
    $residente = $atributos['residente'] ?? Residente::factory()->create();
    $apartamento = $atributos['apartamento'] ?? Apartamento::factory()->create();
    $residente->apartamentos()->syncWithoutDetaching([$apartamento->id]);

    return Paquete::query()->create(array_merge([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Mensajería Ágil',
        'guia' => 'GUIA-'.fake()->unique()->numerify('####'),
        'detalles' => 'Paquete de prueba',
        'estado' => 'Pendiente',
    ], collect($atributos)->except(['residente', 'apartamento'])->all()));
}

test('administrator can access reports while other roles are forbidden', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = User::factory()->create(['rol' => 'residente']);

    $this->actingAs($admin)->get(route('reportes'))->assertOk()->assertSee('Reportes de Paquetería');
    $this->actingAs($vigilante)->get(route('reportes'))->assertForbidden();
    $this->actingAs($residente)->get(route('reportes'))->assertForbidden();
});

test('reports calculate package flow and average pickup time from delivered packages only', function () {
    Carbon::setTestNow('2026-09-02 12:00:00');
    $admin = User::factory()->create(['rol' => 'admin']);
    $guarda = User::factory()->create(['rol' => 'vigilante']);
    $entregadoUno = crearPaqueteReporte(['estado' => 'Entregado', 'fecha_entrega' => now()->subHours(10), 'entregado_por' => $guarda->id, 'recibido_por' => 'Ana Pérez']);
    $entregadoDos = crearPaqueteReporte(['estado' => 'Entregado', 'fecha_entrega' => now()->subHours(20)]);
    Paquete::whereKey($entregadoUno)->update(['created_at' => now()->subHours(14), 'updated_at' => now()->subHours(14)]);
    Paquete::whereKey($entregadoDos)->update(['created_at' => now()->subHours(26), 'updated_at' => now()->subHours(26)]);
    crearPaqueteReporte(['guia' => 'PENDIENTE-ANTIGUO']);

    $this->actingAs($admin)->get(route('reportes', ['dias_custodia' => 3]))
        ->assertOk()
        ->assertViewHas('total', 3)
        ->assertViewHas('pendientes', 1)
        ->assertViewHas('entregados', 2)
        ->assertViewHas('promedioHoras', 5.0)
        ->assertSee('Ana Pérez');

    Carbon::setTestNow();
});

test('reports apply date and state filters and keep them in pagination', function () {
    Carbon::setTestNow('2026-09-02 12:00:00');
    $admin = User::factory()->create(['rol' => 'admin']);
    $viejo = crearPaqueteReporte(['guia' => 'GUIA-VIEJA']);
    Paquete::whereKey($viejo)->update(['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);
    foreach (range(1, 16) as $numero) {
        crearPaqueteReporte(['guia' => "PENDIENTE-{$numero}"]);
    }
    crearPaqueteReporte(['estado' => 'Entregado', 'guia' => 'ENTREGADO-FUERA', 'fecha_entrega' => now()]);

    $respuesta = $this->actingAs($admin)->get(route('reportes', ['fecha_desde' => '2026-09-01', 'fecha_hasta' => '2026-09-02', 'estado' => 'Pendiente']));
    $respuesta->assertOk()->assertDontSee('GUIA-VIEJA')->assertDontSee('ENTREGADO-FUERA')->assertSee('PENDIENTE-1');
    expect($respuesta->viewData('paquetes')->nextPageUrl())->toContain('estado=Pendiente')->and($respuesta->viewData('paquetes')->nextPageUrl())->toContain('fecha_desde=2026-09-01');

    Carbon::setTestNow();
});

test('reports identify only old pending packages in custody and handle no deliveries', function () {
    Carbon::setTestNow('2026-09-10 12:00:00');
    $admin = User::factory()->create(['rol' => 'admin']);
    $antiguo = crearPaqueteReporte(['guia' => 'CUSTODIA-ANTIGUO']);
    Paquete::whereKey($antiguo)->update(['created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)]);
    crearPaqueteReporte(['guia' => 'CUSTODIA-RECIENTE']);
    crearPaqueteReporte(['estado' => 'Entregado', 'guia' => 'ENTREGADO-ANTIGUO', 'fecha_entrega' => now()->subDay()]);

    $this->actingAs($admin)->get(route('reportes', ['dias_custodia' => 3]))
        ->assertViewHas('enCustodia', 1)
        ->assertSee('CUSTODIA-ANTIGUO')
        ->assertSee('CUSTODIA-RECIENTE');

    Paquete::query()->where('estado', 'Entregado')->delete();
    $this->actingAs($admin)->get(route('reportes'))->assertViewHas('promedioHoras', null)->assertSee('Sin datos');
    Carbon::setTestNow();
});

test('csv export respects filters and contains utf8 headers with historical relations', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $guardaInactivo = User::factory()->create(['rol' => 'vigilante', 'name' => 'Guarda Histórico', 'activo' => false]);
    $residenteInactivo = Residente::factory()->create(['nombre' => 'María Histórica', 'activo' => false]);
    $apartamentoInactivo = Apartamento::factory()->create(['torre' => 'Torre Ñ', 'numero' => '301', 'activo' => false]);
    $incluido = crearPaqueteReporte(['residente' => $residenteInactivo, 'apartamento' => $apartamentoInactivo, 'estado' => 'Entregado', 'guia' => 'GUIA-INCLUIDA', 'fecha_entrega' => now(), 'entregado_por' => $guardaInactivo->id, 'recibido_por' => 'Tercero Autorizado']);
    Paquete::whereKey($incluido)->update(['created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)]);
    crearPaqueteReporte(['estado' => 'Pendiente', 'guia' => 'GUIA-EXCLUIDA']);

    $respuesta = $this->actingAs($admin)->get(route('reportes.exportar', ['estado' => 'Entregado']));
    $respuesta->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $respuesta->streamedContent();
    expect($csv)->toStartWith("\xEF\xBB\xBF")->toContain('Fecha recepción')->toContain('Guía')->toContain('María Histórica')->toContain('Torre Ñ')->toContain('Guarda Histórico')->toContain('GUIA-INCLUIDA')->not->toContain('GUIA-EXCLUIDA');
});
