<?php

use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests are redirected to the login page for protected pages', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'vigilante@example.com',
        'password' => 'ValleSanRemo2026!',
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'ValleSanRemo2026!',
    ])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('the seeded Juan Piedradita user is available for login', function () {
    $this->seed('Database\\Seeders\\DemoUserSeeder');

    $user = User::where('name', 'Juan Piedradita')->first();

    expect($user)->not()->toBeNull()
        ->and($user->email)->toBe('juan.piedradita@valledesanremo.test')
        ->and(Hash::check('ValleSanRemo2026!', $user->password))->toBeTrue();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'ValleSanRemo2026!',
    ])->assertRedirect(route('dashboard'));
});

test('users cannot authenticate with invalid credentials', function () {
    User::factory()->create([
        'email' => 'vigilante@example.com',
        'password' => 'ValleSanRemo2026!',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'vigilante@example.com',
            'password' => 'credencial-invalida',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login sends each role to its panel despite a previously requested protected page', function (string $rol, string $destino, string $paginaPrevia) {
    $user = User::factory()->create([
        'rol' => $rol,
        'residente_id' => $rol === 'residente' ? Residente::factory()->create()->id : null,
        'password' => 'password-segura',
    ]);

    $this->get($paginaPrevia)->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password-segura',
    ])->assertRedirect(route($destino))->assertSessionMissing('url.intended');

    $this->assertAuthenticatedAs($user);
    $this->get(route($destino))->assertOk();
    $this->get($paginaPrevia)->assertForbidden();
})->with([
    'admin from root' => ['admin', 'dashboard', '/'],
    'vigilante from administration' => ['vigilante', 'dashboard', '/residentes'],
    'residente from root' => ['residente', 'dashboard-residente', '/'],
]);

test('residents logging in directly reach their panel', function () {
    $user = User::factory()->create([
        'rol' => 'residente',
        'residente_id' => Residente::factory()->create()->id,
        'password' => 'password-segura',
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password-segura',
    ])->assertRedirect(route('dashboard-residente'));

    $this->get(route('dashboard-residente'))->assertOk();
});

test('vigilantes cannot access administrative routes', function () {
    $user = User::factory()->create(['rol' => 'vigilante']);

    $this->actingAs($user)
        ->get(route('residentes'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('configuracion'))
        ->assertForbidden();
});

test('administrators can access administrative routes', function () {
    $user = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($user)
        ->get(route('residentes'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('configuracion'))
        ->assertOk();
});

test('each role sees only its permitted navigation options', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);

    $this->actingAs($vigilante)
        ->get(route('dashboard'))
        ->assertSeeText('Registrar Paquete')
        ->assertSeeText('Consultar Entregas')
        ->assertDontSeeText('Residentes')
        ->assertDontSeeText('Configuración');

    $administrator = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($administrator)
        ->get(route('dashboard'))
        ->assertSeeText('Residentes')
        ->assertSeeText('Configuración')
        ->assertDontSeeText('Registrar Paquete')
        ->assertDontSeeText('Consultar Entregas');
});

test('authenticated users can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
