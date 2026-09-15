<?php

use App\Models\Apartamento;
use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function firmaParaAutorizacion(): string
{
    $imagen = imagecreatetruecolor(120, 60);
    imagealphablending($imagen, false);
    imagesavealpha($imagen, true);
    imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 255, 255, 255, 127));
    imagealphablending($imagen, true);
    imageline($imagen, 10, 45, 100, 15, imagecolorallocate($imagen, 15, 23, 42));
    ob_start();
    imagepng($imagen);
    $contenido = (string) ob_get_clean();
    imagedestroy($imagen);

    return 'data:image/png;base64,'.base64_encode($contenido);
}

function paquetePendienteParaAutorizacion(): array
{
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'guia' => 'AUT-123456',
        'estado' => 'Pendiente',
    ]);
    $usuario = User::factory()->create(['rol' => 'residente', 'residente_id' => $residente->id]);

    return compact('residente', 'apartamento', 'paquete', 'usuario');
}

function datosAutorizacion(): array
{
    return [
        'nombre_autorizado' => 'Pedro Pérez',
        'documento_autorizado' => '123456789',
        'relacion' => 'Familiar',
        'observacion' => 'Presenta su documento en portería.',
    ];
}

test('a resident can authorize a third party for their pending package', function () {
    ['residente' => $residente, 'paquete' => $paquete, 'usuario' => $usuario] = paquetePendienteParaAutorizacion();

    $this->actingAs($usuario)
        ->post(route('autorizaciones.store', $paquete), datosAutorizacion())
        ->assertRedirect(route('mis-paquetes'))
        ->assertSessionHas('success', 'Persona autorizada correctamente para retirar este paquete.');

    $this->assertDatabaseHas('autorizaciones_retiro', [
        'paquete_id' => $paquete->id,
        'residente_id' => $residente->id,
        'nombre_autorizado' => 'Pedro Pérez',
        'estado' => AutorizacionRetiro::ACTIVA,
    ]);

    $this->actingAs($usuario)
        ->get(route('mis-paquetes'))
        ->assertSeeText('Autorizado para retiro')
        ->assertSeeText('Pedro Pérez');
});

test('a resident cannot authorize another residents package or an delivered package', function () {
    ['paquete' => $paquete, 'usuario' => $usuario] = paquetePendienteParaAutorizacion();
    ['paquete' => $paqueteAjeno] = paquetePendienteParaAutorizacion();

    $this->actingAs($usuario)
        ->post(route('autorizaciones.store', $paqueteAjeno), datosAutorizacion())
        ->assertForbidden();

    $paquete->update(['estado' => 'Entregado']);

    $this->actingAs($usuario)
        ->from(route('mis-paquetes'))
        ->post(route('autorizaciones.store', $paquete), datosAutorizacion())
        ->assertRedirect(route('mis-paquetes'))
        ->assertSessionHasErrors('autorizacion');
});

test('a resident can cancel an active authorization and create a replacement', function () {
    ['paquete' => $paquete, 'usuario' => $usuario] = paquetePendienteParaAutorizacion();

    $this->actingAs($usuario)->post(route('autorizaciones.store', $paquete), datosAutorizacion());
    $autorizacion = AutorizacionRetiro::query()->sole();

    $this->actingAs($usuario)
        ->from(route('mis-paquetes'))
        ->post(route('autorizaciones.store', $paquete), datosAutorizacion())
        ->assertRedirect(route('mis-paquetes'))
        ->assertSessionHasErrors('autorizacion');

    $this->actingAs($usuario)
        ->patch(route('autorizaciones.cancelar', [$paquete, $autorizacion]))
        ->assertRedirect(route('mis-paquetes'))
        ->assertSessionHas('success', 'Autorización cancelada correctamente.');

    expect($autorizacion->fresh()->estado)->toBe(AutorizacionRetiro::CANCELADA);

    $this->actingAs($usuario)->post(route('autorizaciones.store', $paquete), [
        ...datosAutorizacion(),
        'nombre_autorizado' => 'Lucía Pérez',
    ]);

    $this->assertDatabaseHas('autorizaciones_retiro', [
        'paquete_id' => $paquete->id,
        'nombre_autorizado' => 'Lucía Pérez',
        'estado' => AutorizacionRetiro::ACTIVA,
    ]);
});

test('a guard can verify and deliver a package to its active authorized third party', function () {
    ['paquete' => $paquete] = paquetePendienteParaAutorizacion();
    $autorizacion = AutorizacionRetiro::query()->create([
        ...datosAutorizacion(),
        'paquete_id' => $paquete->id,
        'residente_id' => $paquete->residente_id,
        'estado' => AutorizacionRetiro::ACTIVA,
    ]);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);

    $this->actingAs($vigilante)
        ->get(route('consultar'))
        ->assertOk()
        ->assertSeeText('Tercero autorizado')
        ->assertSeeText('123456789');

    $this->actingAs($vigilante)
        ->get(route('entrega', $paquete))
        ->assertOk()
        ->assertSeeText('Pedro Pérez')
        ->assertSee('value="autorizado"', false);

    $this->actingAs($vigilante)
        ->post(route('entrega.confirmar', $paquete), [
            'tipo_receptor' => 'autorizado',
            'firma' => firmaParaAutorizacion(),
        ])
        ->assertRedirect(route('historial'));

    $paquete->refresh();
    expect($paquete->estado)->toBe('Entregado')
        ->and($paquete->recibido_por)->toBe('Pedro Pérez')
        ->and($paquete->entregado_por)->toBe($vigilante->id)
        ->and($paquete->fecha_entrega)->not->toBeNull()
        ->and($autorizacion->fresh()->estado)->toBe(AutorizacionRetiro::UTILIZADA);
});

test('a personal delivery cancels an active authorization and guards cannot manage authorizations', function () {
    ['paquete' => $paquete, 'usuario' => $usuario, 'residente' => $residente] = paquetePendienteParaAutorizacion();
    $autorizacion = AutorizacionRetiro::query()->create([
        ...datosAutorizacion(),
        'paquete_id' => $paquete->id,
        'residente_id' => $residente->id,
        'estado' => AutorizacionRetiro::ACTIVA,
    ]);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);

    $this->actingAs($vigilante)
        ->post(route('autorizaciones.store', $paquete), datosAutorizacion())
        ->assertForbidden();

    $this->actingAs($vigilante)
        ->patch(route('autorizaciones.cancelar', [$paquete, $autorizacion]))
        ->assertForbidden();

    $this->actingAs($vigilante)
        ->post(route('entrega.confirmar', $paquete), [
            'tipo_receptor' => 'residente',
            'firma' => firmaParaAutorizacion(),
        ]);

    expect($paquete->fresh()->recibido_por)->toBe($residente->nombre)
        ->and($autorizacion->fresh()->estado)->toBe(AutorizacionRetiro::CANCELADA);
});

test('a cancelled authorization cannot be selected for delivery', function () {
    ['paquete' => $paquete, 'residente' => $residente] = paquetePendienteParaAutorizacion();
    AutorizacionRetiro::query()->create([
        ...datosAutorizacion(),
        'paquete_id' => $paquete->id,
        'residente_id' => $residente->id,
        'estado' => AutorizacionRetiro::CANCELADA,
    ]);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);

    $this->actingAs($vigilante)
        ->from(route('entrega', $paquete))
        ->post(route('entrega.confirmar', $paquete), [
            'tipo_receptor' => 'autorizado',
            'firma' => firmaParaAutorizacion(),
        ])
        ->assertRedirect(route('entrega', $paquete))
        ->assertSessionHasErrors('tipo_receptor');

    expect($paquete->fresh()->estado)->toBe('Pendiente');
});
