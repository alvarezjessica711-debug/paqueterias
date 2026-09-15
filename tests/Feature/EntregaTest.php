<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function firmaDigitalValida(): string
{
    $imagen = imagecreatetruecolor(200, 100);
    imagealphablending($imagen, false);
    imagesavealpha($imagen, true);
    imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 255, 255, 255, 127));
    imagealphablending($imagen, true);
    imagesetthickness($imagen, 3);
    imageline($imagen, 20, 70, 180, 30, imagecolorallocate($imagen, 15, 23, 42));

    ob_start();
    imagepng($imagen);
    $contenido = (string) ob_get_clean();
    imagedestroy($imagen);

    return 'data:image/png;base64,'.base64_encode($contenido);
}

test('a guard confirms a pending package delivery with a valid signature and traceability', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($vigilante)
        ->get(route('entrega', $paquete))
        ->assertOk()
        ->assertSee('lienzo-firma')
        ->assertSee('name="firma"', false)
        ->assertSeeText($residente->nombre);

    $this->actingAs($vigilante)
        ->post(route('entrega.confirmar', $paquete), [
            'tipo_receptor' => 'residente',
            'firma' => firmaDigitalValida(),
        ])
        ->assertRedirect(route('historial'))
        ->assertSessionHas('success', 'Paquete entregado correctamente.');

    $paquete->refresh();

    expect($paquete->estado)->toBe('Entregado')
        ->and($paquete->fecha_entrega)->not->toBeNull()
        ->and($paquete->entregado_por)->toBe($vigilante->id)
        ->and($paquete->recibido_por)->toBe($residente->nombre)
        ->and($paquete->firma)->toStartWith('data:image/png;base64,');
});

test('a delivery requires a valid signature', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($vigilante)
        ->from(route('entrega', $paquete))
        ->post(route('entrega.confirmar', $paquete), ['tipo_receptor' => 'residente'])
        ->assertRedirect(route('entrega', $paquete))
        ->assertSessionHasErrors('firma');

    expect($paquete->fresh()->estado)->toBe('Pendiente');
});

test('a delivered package cannot be delivered twice', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $fechaEntrega = now()->subMinute();
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'estado' => 'Entregado',
        'firma' => firmaDigitalValida(),
        'entregado_por' => $vigilante->id,
        'fecha_entrega' => $fechaEntrega,
        'recibido_por' => $residente->nombre,
    ]);

    $this->actingAs($vigilante)
        ->post(route('entrega.confirmar', $paquete), ['firma' => firmaDigitalValida()])
        ->assertRedirect(route('historial'))
        ->assertSessionHas('error', 'Este paquete ya fue entregado.');

    expect($paquete->fresh()->fecha_entrega->format('Y-m-d H:i:s'))->toBe($fechaEntrega->format('Y-m-d H:i:s'))
        ->and($paquete->fresh()->entregado_por)->toBe($vigilante->id);
});

test('residents and administrators cannot confirm deliveries', function () {
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    $paquete = Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'estado' => 'Pendiente',
    ]);

    $usuarioResidente = User::factory()->create(['rol' => 'residente', 'residente_id' => $residente->id]);
    $admin = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($usuarioResidente)
        ->post(route('entrega.confirmar', $paquete), ['firma' => firmaDigitalValida()])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('entrega.confirmar', $paquete), ['firma' => firmaDigitalValida()])
        ->assertForbidden();

    expect($paquete->fresh()->estado)->toBe('Pendiente');
});
