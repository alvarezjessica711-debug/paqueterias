<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function apiToken(User $user): string
{
    $token = str_repeat('t', 64);
    Cache::put('api-token:'.hash('sha256', $token), $user->id, now()->addHour());
    return $token;
}

function apiResidenteConApartamento(array $residentAttributes = []): array
{
    static $apartmentNumber = 0;
    $apartmentNumber++;
    $resident = Residente::query()->create(['nombre' => 'Residente API', ...$residentAttributes]);
    $apartment = Apartamento::query()->create(['codigo' => 'F'.(100 + $apartmentNumber)]);
    $resident->casas()->attach($apartment);
    return [$resident, $apartment];
}

test('API authentication issues a bearer token, rejects bad credentials and inactive accounts', function () {
    $user = User::factory()->create(['email' => 'api@example.test', 'password' => Hash::make('password123'), 'rol' => 'vigilante']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertUnauthorized();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertOk()->assertJsonPath('user.rol', 'vigilante')->assertJsonMissingPath('user.password');

    $user->update(['activo' => false]);
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->assertForbidden();
});

test('a token returned by login is rejected after logout when reused on package list', function () {
    $user = User::factory()->create([
        'email' => 'logout-flow@example.test',
        'password' => Hash::make('password123'),
        'rol' => 'admin',
    ]);

    $login = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertOk()->assertJsonPath('data.tipo', 'Bearer');

    $token = $login->json('data.token');
    expect($token)->toBeString()->not->toBeEmpty();

    $this->withToken($token)->postJson('/api/logout')->assertOk();
    $this->withToken($token)->getJson('/api/paquetes')->assertUnauthorized();
});

test('API requires a token, enforces roles and revokes tokens on logout', function () {
    $this->getJson('/api/paquetes')->assertUnauthorized();
    $resident = User::factory()->create(['rol' => 'residente']);
    $token = apiToken($resident);

    $this->withToken($token)->postJson('/api/paquetes', [])->assertForbidden();
    $this->withToken($token)->postJson('/api/logout')->assertOk();
    $this->withToken($token)->getJson('/api/paquetes')->assertUnauthorized();
});

test('residents only see their packages and cannot read another residents package', function () {
    [$ownResident, $ownApartment] = apiResidenteConApartamento();
    [$otherResident, $otherApartment] = apiResidenteConApartamento(['nombre' => 'Otro Residente']);
    $ownPackage = Paquete::query()->create(['residente_id' => $ownResident->id, 'apartamento_id' => $ownApartment->id, 'empresa' => 'Envio', 'guia' => 'OWN-1', 'estado' => 'Pendiente']);
    $otherPackage = Paquete::query()->create(['residente_id' => $otherResident->id, 'apartamento_id' => $otherApartment->id, 'empresa' => 'Envio', 'guia' => 'OTHER-1', 'estado' => 'Pendiente']);
    $user = User::factory()->create(['rol' => 'residente', 'residente_id' => $ownResident->id]);

    $this->withToken(apiToken($user))->getJson('/api/paquetes')->assertOk()->assertJsonFragment(['guia' => 'OWN-1'])->assertJsonMissing(['guia' => 'OTHER-1']);
    $this->withToken(apiToken($user))->getJson('/api/paquetes/'.$otherPackage->id)->assertForbidden();
    $this->withToken(apiToken($user))->getJson('/api/paquetes/'.$ownPackage->id)->assertOk()->assertJsonMissingPath('data.codigo_entrega');
    $this->withToken(apiToken($user))->getJson('/api/paquetes/'.$otherPackage->id.'/historial')->assertForbidden();
    $this->withToken(apiToken($user))->getJson('/api/paquetes/'.$ownPackage->id.'/historial')->assertForbidden();
});

test('package list filters and pagination are validated and bounded', function () {
    [$resident, $apartment] = apiResidenteConApartamento();
    Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'PEND-1', 'estado' => 'Pendiente']);
    Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'DONE-1', 'estado' => 'Entregado']);
    $admin = User::factory()->create(['rol' => 'admin']);
    $token = apiToken($admin);

    $this->withToken($token)->getJson('/api/paquetes?estado=Pendiente&per_page=1')->assertOk()->assertJsonPath('total', 1)->assertJsonFragment(['guia' => 'PEND-1']);
    $this->withToken($token)->getJson('/api/paquetes?estado=Inventado')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/paquetes?per_page=101')->assertUnprocessable();
});

test('resident lookup is role protected and exposes only necessary fields', function () {
    [$resident] = apiResidenteConApartamento(['telefono' => '5551234', 'correo' => 'private@example.test']);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $this->withToken(apiToken($guard))->getJson('/api/residentes?busqueda=Residente')->assertOk()
        ->assertJsonFragment(['nombre' => 'Residente API'])->assertJsonFragment(['codigo' => 'F104'])->assertJsonMissing(['telefono' => '5551234'])->assertJsonMissing(['correo' => 'private@example.test']);

    $residentUser = User::factory()->create(['rol' => 'residente', 'residente_id' => $resident->id]);
    $this->withToken(apiToken($residentUser))->getJson('/api/residentes')->assertForbidden();
});

test('vigilante can register a package and invalid resident house relation is rejected', function () {
    Notification::fake();
    [$resident, $apartment] = apiResidenteConApartamento();
    [$otherResident, $otherApartment] = apiResidenteConApartamento(['nombre' => 'Otro Residente']);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $token = apiToken($guard);

    $this->withToken($token)->postJson('/api/paquetes', [
        'residente_id' => $resident->id, 'casa_id' => $apartment->id,
        'empresa' => 'Mensajería', 'guia' => 'API-GUIA-1', 'destinatario' => 'Ana Pérez', 'direccion' => 'F105',
    ])->assertCreated()->assertJsonPath('data.estado', 'Pendiente')->assertJsonPath('data.destinatario', 'Ana Pérez')->assertJsonPath('data.direccion', 'F105')->assertJsonPath('data.casa.codigo', 'F105')->assertJsonMissingPath('data.codigo_entrega');

    $this->withToken($token)->postJson('/api/paquetes', [
        'residente_id' => $resident->id, 'casa_id' => $otherApartment->id,
        'empresa' => 'Mensajería', 'guia' => 'API-GUIA-2', 'destinatario' => 'Ana', 'direccion' => 'F105',
    ])->assertUnprocessable();
    $this->withToken($token)->postJson('/api/paquetes', [])->assertUnprocessable();

    $valid = ['residente_id' => $resident->id, 'casa_id' => $apartment->id, 'empresa' => 'Mensajería', 'guia' => 'API-GUIA-3', 'destinatario' => 'Ana', 'direccion' => 'F105'];
    $this->withToken($token)->postJson('/api/paquetes', [...$valid, 'destinatario' => ''])->assertUnprocessable()->assertJsonValidationErrors('destinatario');
    $this->withToken($token)->postJson('/api/paquetes', [...$valid, 'destinatario' => str_repeat('x', 256)])->assertUnprocessable()->assertJsonValidationErrors('destinatario');
    $this->withToken($token)->postJson('/api/paquetes', [...$valid, 'direccion' => ''])->assertCreated()->assertJsonPath('data.direccion', 'F105');
});

test('vigilante can update pending packages with validation, history and not found responses', function () {
    [$resident, $apartment] = apiResidenteConApartamento();
    $package = Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'UPDATE-1', 'estado' => 'Pendiente']);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $token = apiToken($guard);
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['destinatario' => 'Ana'])
        ->assertOk()->assertJsonPath('data.destinatario', 'Ana')->assertJsonPath('data.casa.codigo', $apartment->codigo);
    $this->assertDatabaseHas('paquete_auditorias', ['paquete_id' => $package->id, 'user_id' => $guard->id, 'campo' => 'destinatario', 'valor_anterior' => null, 'valor_nuevo' => 'Ana']);
    $firstAudit = $package->auditorias()->where('campo', 'destinatario')->firstOrFail();
    expect($firstAudit->created_at)->not->toBeNull();
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['destinatario' => 'María'])->assertOk();
    $this->assertDatabaseHas('paquete_auditorias', ['paquete_id' => $package->id, 'user_id' => $guard->id, 'campo' => 'destinatario', 'valor_anterior' => 'Ana', 'valor_nuevo' => 'María']);
    expect($package->auditorias()->where('campo', 'destinatario')->count())->toBe(2);
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['destinatario' => str_repeat('x', 256)])->assertUnprocessable()->assertJsonValidationErrors('destinatario');
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['direccion' => ''])->assertUnprocessable()->assertJsonValidationErrors('direccion');
    $this->withToken($token)->patchJson('/api/paquetes/'.$package->id, ['estado' => 'Entregado'])->assertUnprocessable()->assertJsonValidationErrors('estado');
    $this->withToken($token)->getJson('/api/paquetes/999999')->assertNotFound();
    $this->withToken($token)->deleteJson('/api/paquetes/999999')->assertMethodNotAllowed();
    $this->withToken($token)->deleteJson('/api/paquetes/'.$package->id)->assertMethodNotAllowed();
    expect(Paquete::query()->find($package->id))->not->toBeNull();
});

test('residents and administrators cannot update, deliver or delete packages', function () {
    [$resident, $apartment] = apiResidenteConApartamento();
    $package = Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'PERM-1', 'estado' => 'Pendiente']);
    $residentUser = User::factory()->create(['rol' => 'residente', 'residente_id' => $resident->id]);
    $this->withToken(apiToken($residentUser))->patchJson('/api/paquetes/'.$package->id, ['destinatario' => 'Ana'])->assertForbidden();
    $this->withToken(apiToken($residentUser))->deleteJson('/api/paquetes/'.$package->id)->assertMethodNotAllowed();
    $admin = User::factory()->create(['rol' => 'admin']);
    $adminToken = apiToken($admin);
    $this->withToken($adminToken)->getJson('/api/paquetes')->assertOk();
    $this->withToken($adminToken)->putJson('/api/paquetes/'.$package->id, ['destinatario' => 'Admin'])->assertForbidden();
    $this->withToken($adminToken)->postJson('/api/paquetes/'.$package->id.'/entrega', [])->assertForbidden();
    $this->withToken($adminToken)->deleteJson('/api/paquetes/'.$package->id)->assertMethodNotAllowed();
    $this->withToken($adminToken)->getJson('/api/paquetes/'.$package->id.'/historial')->assertOk();
});

test('vigilante delivery requires its own valid single-use code and records traceability', function () {
    [$resident, $apartment] = apiResidenteConApartamento();
    $package = Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'DELIVER-1', 'estado' => 'Pendiente', 'destinatario' => $resident->nombre]);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $token = apiToken($guard);

    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', ['tipo_receptor' => 'residente'])
        ->assertStatus(409)->assertJsonPath('message', 'Valida primero el código de entrega de este paquete. La validación vence en cinco minutos.');
    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega/validar', ['codigo_entrega' => 'CODIGO-ERR'])
        ->assertUnprocessable();
    expect($package->fresh()->estado)->toBe('Pendiente');

    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega/validar', ['codigo_entrega' => $package->codigo_entrega])
        ->assertOk()->assertJsonPath('codigo_validado', true);
    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', ['tipo_receptor' => 'residente'])
        ->assertOk()->assertJsonPath('data.estado', 'Entregado')
        ->assertJsonPath('data.entrega.vigilante', $guard->name)
        ->assertJsonMissingPath('data.codigo_entrega');
    expect($package->fresh()->entregado_por)->toBe($guard->id)
        ->and($package->fresh()->codigo_entrega_usado_en)->not->toBeNull();
    $this->assertDatabaseHas('paquete_auditorias', ['paquete_id' => $package->id, 'user_id' => $guard->id, 'accion' => 'entregado', 'campo' => 'estado', 'valor_anterior' => 'Pendiente', 'valor_nuevo' => 'Entregado']);

    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega/validar', ['codigo_entrega' => $package->codigo_entrega])->assertConflict();
    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', ['tipo_receptor' => 'residente'])->assertConflict();
});

test('unauthorized roles cannot register deliveries or list residents', function () {
    $resident = User::factory()->create(['rol' => 'residente']);
    [$packageResident, $apartment] = apiResidenteConApartamento();
    $package = Paquete::query()->create(['residente_id' => $packageResident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'ROLE-1', 'estado' => 'Pendiente']);
    $token = apiToken($resident);
    $this->withToken($token)->postJson('/api/paquetes', [])->assertForbidden();
    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', [])->assertForbidden();
    $this->withToken($token)->getJson('/api/residentes')->assertForbidden();
});
