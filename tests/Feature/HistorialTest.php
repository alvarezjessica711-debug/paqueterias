<?php

use App\Models\Apartamento;
use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the administrator can audit delivery traceability in history', function () {
    $user = User::factory()->create(['rol' => 'admin']);
    $vigilante = User::factory()->create(['name' => 'Vigilante de entrega', 'rol' => 'vigilante']);
    $residente = Residente::factory()->create([
        'nombre' => 'Camila Torres',
    ]);
    $apartamento = Apartamento::factory()->create(['torre' => 'Torre 2', 'numero' => '203']);
    $residente->apartamentos()->attach($apartamento);
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Coordinadora',
        'guia' => 'COOR-HISTORIAL-001',
        'detalles' => 'Sobre',
        'estado' => 'Entregado',
        'entregado_por' => $vigilante->id,
        'fecha_entrega' => now(),
        'recibido_por' => 'Camila Torres',
        'firma' => 'data:image/png;base64,firma-de-prueba',
    ]);
    AutorizacionRetiro::query()->create([
        'paquete_id' => $paquete->id,
        'residente_id' => $residente->id,
        'nombre_autorizado' => 'Pedro Pérez',
        'documento_autorizado' => '123456789',
        'relacion' => 'Familiar',
        'estado' => AutorizacionRetiro::UTILIZADA,
    ]);

    $this->actingAs($user)
        ->get(route('historial'))
        ->assertOk()
        ->assertSeeText($paquete->created_at->format('d/m/Y H:i'))
        ->assertSeeText('Camila Torres')
        ->assertSeeText('COOR-HISTORIAL-001')
        ->assertSeeText('Vigilante de entrega')
        ->assertSeeText('Recibido por:')
        ->assertSeeText('Pedro Pérez')
        ->assertSeeText('123456789')
        ->assertSeeText('Estado autorización:')
        ->assertSeeText('Utilizada')
        ->assertSeeText('Ver firma')
        ->assertDontSeeText('08/05/2024');
});
