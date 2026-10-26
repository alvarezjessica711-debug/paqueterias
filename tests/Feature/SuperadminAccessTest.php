<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function superadminToken(User $user): string
{
    $token = str_repeat('s', 64);
    Cache::put('api-token:'.hash('sha256', $token), $user->id, now()->addHour());

    return $token;
}

test('superadmin creates permitted roles through the API without exposing passwords', function () {
    $superadmin = User::factory()->create(['rol' => 'superadmin']);
    $token = superadminToken($superadmin);

    $this->withToken($token)->postJson('/api/users', [
        'name' => 'Administrador Nuevo',
        'email' => 'admin-nuevo@example.test',
        'password' => 'UnaClaveSegura2026',
        'password_confirmation' => 'UnaClaveSegura2026',
        'rol' => 'admin',
    ])->assertCreated()->assertJsonPath('data.rol', 'admin')->assertJsonMissingPath('data.password');

    $created = User::query()->where('email', 'admin-nuevo@example.test')->firstOrFail();
    expect(Hash::check('UnaClaveSegura2026', $created->password))->toBeTrue();
});

test('only superadmin may create users and other roles cannot assign roles by request', function () {
    foreach (['admin', 'vigilante', 'residente'] as $role) {
        $user = User::factory()->create(['rol' => $role]);
        $this->withToken(superadminToken($user))->postJson('/api/users', [
            'name' => 'Intento',
            'email' => $role.'-blocked@example.test',
            'password' => 'UnaClaveSegura2026',
            'password_confirmation' => 'UnaClaveSegura2026',
            'rol' => 'superadmin',
        ])->assertForbidden();
    }

    $superadmin = User::factory()->create(['rol' => 'superadmin']);
    $this->withToken(superadminToken($superadmin))->postJson('/api/users', [
        'name' => 'Segundo propietario',
        'email' => 'segundo-superadmin@example.test',
        'password' => 'UnaClaveSegura2026',
        'password_confirmation' => 'UnaClaveSegura2026',
        'rol' => 'superadmin',
    ])->assertCreated()->assertJsonPath('data.rol', 'superadmin');

    $this->actingAs(User::factory()->create(['rol' => 'admin']))
        ->post(route('usuarios.store'), [])->assertForbidden();
});

test('superadmin creates resident accounts with a valid resident association and reads package audit without write permissions', function () {
    $superadmin = User::factory()->create(['rol' => 'superadmin']);
    $token = superadminToken($superadmin);
    $resident = Residente::factory()->create();
    $apartment = Apartamento::factory()->create();
    $resident->apartamentos()->attach($apartment);
    $package = Paquete::query()->create([
        'residente_id' => $resident->id,
        'apartamento_id' => $apartment->id,
        'empresa' => 'Envíos',
        'guia' => 'SUPER-1',
        'estado' => 'Pendiente',
    ]);

    $this->withToken($token)->postJson('/api/users', [
        'name' => $resident->nombre,
        'email' => 'residente-nuevo@example.test',
        'password' => 'UnaClaveSegura2026',
        'password_confirmation' => 'UnaClaveSegura2026',
        'rol' => 'residente',
        'residente_id' => $resident->id,
    ])->assertCreated()->assertJsonPath('data.residente_id', $resident->id);

    $this->withToken($token)->getJson('/api/paquetes')->assertOk()->assertJsonFragment(['guia' => 'SUPER-1']);
    $this->withToken($token)->getJson('/api/paquetes/'.$package->id.'/historial')->assertOk();
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['empresa' => 'Cambio'])->assertForbidden();
    $this->withToken($token)->postJson('/api/paquetes', [])->assertForbidden();
    $this->withToken($token)->deleteJson('/api/paquetes/'.$package->id)->assertMethodNotAllowed();
    expect($package->fresh())->not->toBeNull();
});

test('superadmin console bootstrap prompts for credentials and only creates the first owner', function () {
    $this->artisan('app:superadmin:create')
        ->expectsQuestion('Nombre del superadministrador', 'Propietario')
        ->expectsQuestion('Correo del superadministrador', 'propietario@example.test')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'UnaClaveSegura2026')
        ->expectsQuestion('Confirma la contraseña', 'UnaClaveSegura2026')
        ->expectsOutput('Cuenta superadmin creada. La contraseña no se mostró ni se registró.')
        ->assertSuccessful();

    expect(User::query()->where('rol', 'superadmin')->count())->toBe(1);

    $this->artisan('app:superadmin:create')->assertFailed();
    expect(User::query()->where('rol', 'superadmin')->count())->toBe(1);
});
