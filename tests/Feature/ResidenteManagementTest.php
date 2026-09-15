<?php

use App\Models\Apartamento;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the residents page shows residents stored in the database', function () {
    $user = User::factory()->create(['rol' => 'admin']);
    $residente = Residente::factory()->create([
        'nombre' => 'Laura Gomez',
    ]);

    $this->actingAs($user)
        ->get(route('residentes'))
        ->assertOk()
        ->assertSeeText('Laura Gomez')
        ->assertSeeText('Sin apartamento asociado');
});

test('an authenticated user can register a resident', function () {
    $user = User::factory()->create(['rol' => 'admin']);
    $apartamentos = Apartamento::factory()->count(2)->create();

    $this->actingAs($user)
        ->post(route('residentes.store'), [
            'nombre' => 'Daniela Rojas',
            'telefono' => '312 555 6677',
            'correo' => 'daniela@example.test',
            'apartamentos' => $apartamentos->pluck('id')->all(),
        ])
        ->assertRedirect(route('residentes'))
        ->assertSessionHas('success', 'Residente registrado correctamente.');

    $this->assertDatabaseHas('residentes', [
        'nombre' => 'Daniela Rojas',
        'telefono' => '312 555 6677',
        'correo' => 'daniela@example.test',
    ]);

    $residente = Residente::query()->where('correo', 'daniela@example.test')->firstOrFail();

    expect($residente->apartamentos()->pluck('apartamentos.id')->all())
        ->toEqualCanonicalizing($apartamentos->pluck('id')->all());
});

test('an authenticated user must select at least one apartment when registering a resident', function () {
    $user = User::factory()->create(['rol' => 'admin']);

    $this->actingAs($user)
        ->from(route('residentes'))
        ->post(route('residentes.store'), [
            'nombre' => 'Andres Ramirez',
            'telefono' => '300 111 2233',
            'correo' => 'andres@example.test',
            'apartamentos' => [],
        ])
        ->assertRedirect(route('residentes'))
        ->assertSessionHasErrors('apartamentos');
});
