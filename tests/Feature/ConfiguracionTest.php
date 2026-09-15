<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows the authenticated user to update their configuration settings', function () {
    $user = User::factory()->create([
        'name' => 'Carlos Mendoza',
        'rol' => 'admin',
    ]);

    $this->actingAs($user)
        ->get(route('configuracion'))
        ->assertOk()
        ->assertSeeText('Configuración')
        ->assertSeeText('Perfil del Personal')
        ->assertSeeText('Cambiar contraseña');

    $this->actingAs($user)
        ->post(route('configuracion.perfil'), [
            'nombre' => 'Carlos Mendoza Jr.',
            'turno' => 'Mañana (6:00 AM - 2:00 PM)',
        ])
        ->assertRedirect(route('configuracion'));

    $this->actingAs($user)
        ->post(route('configuracion.mensaje'), [
            'plantilla' => '¡Hola! Tienes un paquete esperando en portería.',
            'activar_wpp' => '1',
        ])
        ->assertRedirect(route('configuracion'));

    $this->actingAs($user)
        ->get(route('configuracion'))
        ->assertOk()
        ->assertSeeText('Perfil del Personal')
        ->assertSeeText('Turno Asignado')
        ->assertSeeText('Cambiar contraseña')
        ->assertSeeText('Mensajes de WhatsApp');
});
