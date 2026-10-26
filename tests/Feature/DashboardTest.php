<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the dashboard displays recently registered packages', function () {
    $user = User::factory()->create();
    $apartamento = Apartamento::factory()->create(['codigo' => 'A20']);

    Paquete::query()->create([
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Servientrega',
        'detalles' => 'Caja mediana',
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('A20')
        ->assertSeeText('Servientrega')
        ->assertSeeText('Pendiente')
        ->assertDontSeeText('María Fernanda López');
});

test('the superadmin dashboard greets the authenticated account and points to public assets on the current host', function () {
    $superadmin = User::factory()->create(['rol' => 'superadmin', 'name' => 'Camila Propietaria']);

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('¡Hola, Camila Propietaria!')
        ->assertSee('href="/estilos.css"', false)
        ->assertSee('src="/Imagenes/logo.png"', false)
        ->assertDontSeeText('¡Hola, Superadministrador!');
});

test('the dashboard shows a readable label for the authenticated user role', function (string $role, string $label) {
    $user = User::factory()->create(['rol' => $role, 'name' => 'Nombre Verificado']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('¡Hola, Nombre Verificado!')
        ->assertSeeText($label);
})->with([
    'vigilante' => ['vigilante', 'Vigilante'],
    'administrador' => ['admin', 'Administrador'],
    'superadministrador' => ['superadmin', 'Superadministrador'],
]);
