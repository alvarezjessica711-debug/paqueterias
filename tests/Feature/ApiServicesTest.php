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

function apiFirmaValida(): string
{
    $image = imagecreatetruecolor(200, 100);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
    imagealphablending($image, true);
    imagesetthickness($image, 3);
    imageline($image, 20, 70, 180, 30, imagecolorallocate($image, 15, 23, 42));
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    imagedestroy($image);
    return 'data:image/png;base64,'.base64_encode($png);
}

function apiResidenteConApartamento(array $residentAttributes = []): array
{
    static $apartmentNumber = 100;
    $apartmentNumber++;
    $resident = Residente::query()->create(['nombre' => 'Residente API', ...$residentAttributes]);
    $apartment = Apartamento::query()->create(['torre' => 'A', 'numero' => (string) $apartmentNumber]);
    $resident->apartamentos()->attach($apartment);
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
    $this->withToken(apiToken($user))->getJson('/api/paquetes/'.$ownPackage->id)->assertOk()->assertJsonMissingPath('data.firma');
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
        ->assertJsonFragment(['nombre' => 'Residente API'])->assertJsonMissing(['telefono' => '5551234'])->assertJsonMissing(['correo' => 'private@example.test']);

    $residentUser = User::factory()->create(['rol' => 'residente', 'residente_id' => $resident->id]);
    $this->withToken(apiToken($residentUser))->getJson('/api/residentes')->assertForbidden();
});

test('vigilante can register a package and invalid resident apartment relation is rejected', function () {
    Notification::fake();
    [$resident, $apartment] = apiResidenteConApartamento();
    [$otherResident, $otherApartment] = apiResidenteConApartamento(['nombre' => 'Otro Residente']);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $token = apiToken($guard);

    $this->withToken($token)->postJson('/api/paquetes', [
        'residente_id' => $resident->id, 'apartamento_id' => $apartment->id,
        'empresa' => 'Mensajería', 'guia' => 'API-GUIA-1',
    ])->assertCreated()->assertJsonPath('data.estado', 'Pendiente')->assertJsonMissingPath('data.firma');

    $this->withToken($token)->postJson('/api/paquetes', [
        'residente_id' => $resident->id, 'apartamento_id' => $otherApartment->id,
        'empresa' => 'Mensajería', 'guia' => 'API-GUIA-2',
    ])->assertUnprocessable();
    $this->withToken($token)->postJson('/api/paquetes', [])->assertUnprocessable();
});

test('vigilante delivery records traceability and duplicate delivery returns conflict without exposing signature', function () {
    [$resident, $apartment] = apiResidenteConApartamento();
    $package = Paquete::query()->create(['residente_id' => $resident->id, 'apartamento_id' => $apartment->id, 'empresa' => 'Envio', 'guia' => 'DELIVER-1', 'estado' => 'Pendiente']);
    $guard = User::factory()->create(['rol' => 'vigilante']);
    $token = apiToken($guard);
    $signature = apiFirmaValida();
    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', ['tipo_receptor' => 'residente', 'firma' => $signature])
        ->assertOk()->assertJsonPath('data.estado', 'Entregado')->assertJsonPath('data.entrega.vigilante', $guard->name)->assertJsonMissingPath('data.firma');
    expect($package->fresh()->entregado_por)->toBe($guard->id)->and($package->fresh()->firma)->toBe($signature);

    $this->withToken($token)->postJson('/api/paquetes/'.$package->id.'/entrega', ['tipo_receptor' => 'residente', 'firma' => $signature])->assertConflict();
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
