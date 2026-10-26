<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\PaqueteAuditoria;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paqueteParaAuditoria(): Paquete
{
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);

    return Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'guia' => 'AUDIT-1',
        'destinatario' => 'Laura',
        'direccion' => 'A1',
        'estado' => 'Pendiente',
    ]);
}

test('history keeps action links available to vigilantes and read-only audit links for administrators', function () {
    $package = paqueteParaAuditoria();
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $vigilanteView = $this->actingAs($vigilante)->get(route('historial'))
        ->assertOk()
        ->assertSee('class="historial-tabla-scroll"', false)
        ->assertSee('class="acciones-historial"', false)
        ->assertSee(route('entrega', $package), false)
        ->assertSee(route('paquetes.edit', $package), false)
        ->assertSee(route('paquetes.auditoria', $package), false);

    $admin = User::factory()->create(['rol' => 'admin']);
    $this->actingAs($admin)->get(route('historial'))
        ->assertOk()
        ->assertSee(route('paquetes.auditoria', $package), false)
        ->assertDontSee(route('entrega', $package), false)
        ->assertDontSee(route('paquetes.edit', $package), false);
});

test('administrator reads package and audit history but cannot change or deliver packages', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $package = paqueteParaAuditoria();
    PaqueteAuditoria::query()->create([
        'paquete_id' => $package->id,
        'user_id' => $admin->id,
        'accion' => 'modificado',
        'campo' => 'guia',
        'valor_anterior' => 'OLD',
        'valor_nuevo' => 'AUDIT-1',
        'created_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('historial'))->assertOk()->assertSee('Auditoría de modificaciones y entregas')->assertSee('AUDIT-1');
    $this->actingAs($admin)->get(route('paquetes.auditoria', $package))->assertOk()->assertSee('OLD');
    $this->actingAs($admin)->get(route('paquetes.edit', $package))->assertForbidden();
    $this->actingAs($admin)->patch(route('paquetes.update', $package), ['guia' => 'MODIFIED'])->assertForbidden();
    $this->actingAs($admin)->post(route('entrega.confirmar', $package), [])->assertForbidden();
});

test('vigilante correction records each changed field and audit records cannot be edited or deleted', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $package = paqueteParaAuditoria();

    $this->actingAs($vigilante)->patch(route('paquetes.update', $package), [
        'empresa' => 'Coordinadora',
        'guia' => 'AUDIT-2',
        'destinatario' => 'Maria',
        'direccion' => 'B2',
        'detalles' => 'Caja pequeña',
    ])->assertRedirect(route('historial'));

    $this->assertDatabaseHas('paquete_auditorias', ['paquete_id' => $package->id, 'user_id' => $vigilante->id, 'campo' => 'empresa', 'valor_anterior' => 'Servientrega', 'valor_nuevo' => 'Coordinadora']);
    $this->assertDatabaseHas('paquete_auditorias', ['paquete_id' => $package->id, 'user_id' => $vigilante->id, 'campo' => 'guia', 'valor_anterior' => 'AUDIT-1', 'valor_nuevo' => 'AUDIT-2']);
    $audit = $package->auditorias()->firstOrFail();
    expect(fn () => $audit->update(['valor_nuevo' => 'manipulado']))->toThrow(\LogicException::class);
    expect(fn () => $audit->delete())->toThrow(\LogicException::class);
});

test('no delete route exists and direct package deletion requests are rejected for each existing role', function () {
    $package = paqueteParaAuditoria();

    foreach (['admin', 'vigilante', 'residente', 'superadmin'] as $role) {
        $user = User::factory()->create(['rol' => $role, 'residente_id' => $role === 'residente' ? $package->residente_id : null]);
        $this->actingAs($user)->deleteJson('/api/paquetes/'.$package->id)->assertMethodNotAllowed();
    }

    expect($package->fresh())->not->toBeNull();
});
