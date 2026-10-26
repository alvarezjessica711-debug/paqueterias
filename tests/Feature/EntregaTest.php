<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function paquetePendienteParaCodigo(): Paquete
{
    $residente = Residente::factory()->create(['nombre' => 'Laura Destinataria']);
    $casa = Apartamento::query()->where('codigo', 'B5')->firstOrFail();
    $residente->casas()->attach($casa);

    return Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $casa->id,
        'empresa' => 'Servientrega',
        'destinatario' => 'Laura Destinataria',
        'direccion' => 'B5',
        'guia' => 'CODE-'.uniqid(),
        'estado' => 'Pendiente',
    ]);
}

test('registering packages generates and associates distinct random delivery codes', function () {
    Notification::fake();
    config(['services.whatsapp.access_token' => null, 'services.whatsapp.phone_number_id' => null]);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'Laura Destinataria']);
    $casa = Apartamento::query()->where('codigo', 'B5')->firstOrFail();
    $residente->casas()->attach($casa);

    foreach (['GUIA-1', 'GUIA-2'] as $guia) {
        $this->actingAs($vigilante)->post(route('paquetes.store'), [
            'casa_id' => $casa->id,
            'empresa' => 'Servientrega',
            'destinatario' => 'Laura Destinataria',
            'guia' => $guia,
        ])->assertRedirect(route('confirmacion'))->assertSessionHas('whatsapp_estado', 'not_configured');
    }

    $paquetes = Paquete::query()->orderBy('id')->get();
    expect($paquetes)->toHaveCount(2)
        ->and($paquetes[0]->codigo_entrega)->toMatch('/^[A-Z0-9]{10}$/')
        ->and($paquetes[0]->codigo_entrega)->not->toBe($paquetes[1]->codigo_entrega)
        ->and($paquetes->every(fn (Paquete $paquete) => $paquete->estado === 'Pendiente'))->toBeTrue();

    $this->assertDatabaseHas('paquetes', ['id' => $paquetes[0]->id, 'codigo_entrega' => $paquetes[0]->codigo_entrega]);
    Notification::assertNothingSent();
});

test('delivery page shows code validation and no signature controls', function () {
    config(['services.whatsapp.access_token' => null, 'services.whatsapp.phone_number_id' => null]);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $paquete = paquetePendienteParaCodigo();

    $this->actingAs($vigilante)->get(route('entrega', $paquete))
        ->assertOk()
        ->assertSeeText('Código único de entrega')
        ->assertSeeText('Validar código')
        ->assertSeeText('Pendiente')
        ->assertSeeText($paquete->codigo_entrega)
        ->assertSeeText('WhatsApp no está configurado; no se realizó ningún envío.')
        ->assertDontSee('lienzo-firma')
                ->assertDontSeeText('Firma de quien recibe');
});

test('an incorrect code is rejected and the package remains pending', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $paquete = paquetePendienteParaCodigo();

    $this->actingAs($vigilante)->from(route('entrega', $paquete))
        ->post(route('entrega.validar', $paquete), ['codigo_entrega' => 'INCORRECTO'])
        ->assertRedirect(route('entrega', $paquete))
        ->assertSessionHasErrors('codigo_entrega');

    expect($paquete->fresh()->estado)->toBe('Pendiente')
        ->and($paquete->fresh()->codigo_entrega_validado_por)->toBeNull();
});

test('a valid code enables confirmation and records delivery audit traceability', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $paquete = paquetePendienteParaCodigo();

    $this->actingAs($vigilante)->from(route('entrega', $paquete))->post(route('entrega.validar', $paquete), ['codigo_entrega' => strtolower($paquete->codigo_entrega)])
        ->assertRedirect(route('entrega', $paquete))
        ->assertSessionHas('success', 'Código válido. Confirma la entrega para continuar.');

    $this->actingAs($vigilante)->get(route('entrega', $paquete))->assertOk()->assertSeeText('Código válido para este paquete.');
    $this->actingAs($vigilante)->from(route('entrega', $paquete))->post(route('entrega.confirmar', $paquete), ['tipo_receptor' => 'residente'])
        ->assertRedirect(route('historial'))
        ->assertSessionHas('success', 'Paquete entregado correctamente.');

    $entregado = $paquete->fresh();
    expect($entregado->estado)->toBe('Entregado')
        ->and($entregado->fecha_entrega)->not->toBeNull()
        ->and($entregado->entregado_por)->toBe($vigilante->id)
        ->and($entregado->recibido_por)->toBe('Laura Destinataria')
        ->and($entregado->codigo_entrega_usado_en)->not->toBeNull()
        ->and($entregado->codigo_entrega_validado_por)->toBeNull();

    $this->assertDatabaseHas('paquete_auditorias', [
        'paquete_id' => $paquete->id,
        'user_id' => $vigilante->id,
        'accion' => 'entregado',
        'campo' => 'estado',
        'valor_anterior' => 'Pendiente',
        'valor_nuevo' => 'Entregado',
    ]);
    $this->assertDatabaseHas('paquete_auditorias', [
        'paquete_id' => $paquete->id,
        'user_id' => $vigilante->id,
        'campo' => 'fecha_entrega',
    ]);
});

test('delivery cannot be confirmed without validation and another packages code cannot validate it', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $paqueteUno = paquetePendienteParaCodigo();
    $paqueteDos = paquetePendienteParaCodigo();

    $this->actingAs($vigilante)->from(route('entrega', $paqueteUno))->post(route('entrega.confirmar', $paqueteUno), ['tipo_receptor' => 'residente'])
        ->assertRedirect(route('entrega', $paqueteUno))
        ->assertSessionHasErrors('codigo_entrega');

    $this->actingAs($vigilante)->from(route('entrega', $paqueteDos))
        ->post(route('entrega.validar', $paqueteDos), ['codigo_entrega' => $paqueteUno->codigo_entrega])
        ->assertSessionHasErrors('codigo_entrega');

    expect($paqueteUno->fresh()->estado)->toBe('Pendiente')
        ->and($paqueteDos->fresh()->estado)->toBe('Pendiente');
});

test('a code cannot be reused to redeliver a completed package', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $paquete = paquetePendienteParaCodigo();

    $this->actingAs($vigilante)->post(route('entrega.validar', $paquete), ['codigo_entrega' => $paquete->codigo_entrega]);
    $this->actingAs($vigilante)->post(route('entrega.confirmar', $paquete), ['tipo_receptor' => 'residente'])->assertSessionHas('success');
    $entregado = $paquete->fresh();
    $fecha = $entregado->fecha_entrega->toISOString();

    $this->actingAs($vigilante)->post(route('entrega.validar', $entregado), ['codigo_entrega' => $entregado->codigo_entrega])
        ->assertRedirect(route('historial'))
        ->assertSessionHas('error', 'Este paquete ya fue entregado.');
    $this->actingAs($vigilante)->post(route('entrega.confirmar', $entregado), ['tipo_receptor' => 'residente'])
        ->assertRedirect(route('historial'))
        ->assertSessionHas('error', 'Este paquete ya fue entregado.');

    expect($entregado->fresh()->fecha_entrega->toISOString())->toBe($fecha)
        ->and($entregado->fresh()->estado)->toBe('Entregado');
});

test('residents and administrators cannot validate or confirm deliveries', function () {
    $paquete = paquetePendienteParaCodigo();
    $residente = User::factory()->create(['rol' => 'residente', 'residente_id' => $paquete->residente_id]);
    $admin = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($residente)->post(route('entrega.validar', $paquete), ['codigo_entrega' => $paquete->codigo_entrega])->assertForbidden();
    $this->actingAs($admin)->post(route('entrega.confirmar', $paquete), ['tipo_receptor' => 'residente'])->assertForbidden();

    expect($paquete->fresh()->estado)->toBe('Pendiente');
});
