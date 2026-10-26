<?php

use App\Models\Apartamento;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('only a superadmin creates an account for an existing resident once', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $superadmin = User::factory()->create(['rol' => 'superadmin']);
    $residente = Residente::factory()->create(['correo' => 'historial@example.test']);
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);

    $this->actingAs($admin)->post(route('residentes.cuenta.store', $residente), [
        'email' => 'acceso@example.test', 'password' => 'password-segura', 'password_confirmation' => 'password-segura',
    ])->assertForbidden();

    $this->actingAs($superadmin)->post(route('residentes.cuenta.store', $residente), [
        'email' => 'acceso@example.test', 'password' => 'password-segura', 'password_confirmation' => 'password-segura',
    ])->assertRedirect(route('usuarios'));

    $user = $residente->fresh()->user;
    expect($user->rol)->toBe('residente')->and($user->residente_id)->toBe($residente->id)->and(Hash::check('password-segura', $user->password))->toBeTrue();
    $this->actingAs($superadmin)->get(route('residentes.cuenta.create', $residente))->assertStatus(422);
});

test('admin manages guards and houses while other roles are forbidden', function () {
    $admin = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($admin)->get(route('guardas'))
        ->assertSeeText('Vigilantes')
        ->assertDontSeeText('Guardas de Seguridad');

    $this->actingAs($admin)->post(route('guardas.store'), ['name' => 'Guarda Nuevo', 'email' => 'guarda@example.test', 'password' => 'password-segura', 'password_confirmation' => 'password-segura'])->assertForbidden();
    $superadmin = User::factory()->create(['rol' => 'superadmin']);
    $this->actingAs($superadmin)->post(route('guardas.store'), ['name' => 'Guarda Nuevo', 'email' => 'guarda@example.test', 'password' => 'password-segura', 'password_confirmation' => 'password-segura'])->assertRedirect(route('usuarios'));
    $guarda = User::where('email', 'guarda@example.test')->firstOrFail();
    expect($guarda->rol)->toBe('vigilante')->and($guarda->residente_id)->toBeNull();
    $this->actingAs($admin)->post(route('casas.store'), ['codigo' => 'F99'])->assertSessionHas('success');
    $this->actingAs($admin)->from(route('casas'))->post(route('casas.store'), ['codigo' => 'F99'])->assertSessionHasErrors('codigo');
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $this->actingAs($vigilante)->get(route('residentes'))->assertForbidden();
    $this->actingAs($vigilante)->get(route('guardas'))->assertForbidden();
    $this->actingAs($vigilante)->get(route('casas'))->assertForbidden();
});

test('inactive accounts cannot log in or use protected routes', function () {
    $user = User::factory()->create(['rol' => 'vigilante', 'activo' => false, 'password' => 'password-segura']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password-segura'])->assertSessionHasErrors('email');
    $this->actingAs($user)->get(route('registro-paquetes'))->assertRedirect(route('login'));
});
