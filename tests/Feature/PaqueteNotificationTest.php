<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use App\Notifications\NuevoPaqueteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('registering a package notifies only the user assigned to its resident', function () {
    Notification::fake();

    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'María Fernanda López']);
    $otroResidente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create(['torre' => 'Torre 2', 'numero' => '204']);
    $residente->apartamentos()->attach($apartamento);
    $usuarioResidente = User::factory()->create(['rol' => 'residente', 'residente_id' => $residente->id]);
    $otroUsuario = User::factory()->create(['rol' => 'residente', 'residente_id' => $otroResidente->id]);

    $this->actingAs($vigilante)
        ->post(route('paquetes.store'), [
            'residente_id' => $residente->id,
            'apartamento_id' => $apartamento->id,
            'empresa' => 'Servientrega',
            'guia' => 'SER123456',
            'detalles' => 'Caja frágil',
        ])
        ->assertRedirect(route('confirmacion'))
        ->assertSessionHas('notificacion_enviada', true);

    $paquete = Paquete::query()->sole();

    expect($paquete->estado)->toBe('Pendiente')
        ->and($paquete->guia)->toBe('SER123456');

    Notification::assertSentTo(
        $usuarioResidente,
        NuevoPaqueteNotification::class,
        function (NuevoPaqueteNotification $notification) use ($usuarioResidente): bool {
            $mail = $notification->toMail($usuarioResidente);

            return $mail->subject === 'Nuevo paquete recibido - Valle de San Remo'
                && in_array('Guía: SER123456', $mail->introLines, true)
                && in_array('Empresa: Servientrega', $mail->introLines, true)
                && in_array('Apartamento: Torre 2 - Apto. 204', $mail->introLines, true)
                && in_array('Estado: Pendiente', $mail->introLines, true)
                && in_array('Observación: Caja frágil', $mail->introLines, true);
        },
    );

    Notification::assertNotSentTo($otroUsuario, NuevoPaqueteNotification::class);
});

test('a package is still registered when its resident has no user account', function () {
    Notification::fake();

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
        ])
        ->assertRedirect(route('confirmacion'))
        ->assertSessionHas('notificacion_enviada', false);

    $this->assertDatabaseHas('paquetes', [
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'guia' => 'COOR-2026-001',
        'estado' => 'Pendiente',
    ]);

    Notification::assertNothingSent();
});
