<?php

use App\Models\Apartamento;
use App\Models\AutorizacionRetiro;
use App\Models\Paquete;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function firmaRnfValida(): string
{
    $imagen = imagecreatetruecolor(200, 100);
    imagealphablending($imagen, false);
    imagesavealpha($imagen, true);
    imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 255, 255, 255, 127));
    imagealphablending($imagen, true);
    imageline($imagen, 20, 70, 180, 30, imagecolorallocate($imagen, 15, 23, 42));
    ob_start();
    imagepng($imagen);
    $contenido = (string) ob_get_clean();
    imagedestroy($imagen);

    return 'data:image/png;base64,'.base64_encode($contenido);
}

function paqueteRnf(array $atributos = []): Paquete
{
    $residente = $atributos['residente'] ?? Residente::factory()->create();
    $apartamento = $atributos['apartamento'] ?? Apartamento::factory()->create();
    $residente->apartamentos()->syncWithoutDetaching([$apartamento->id]);

    return Paquete::query()->create(array_merge([
        'residente_id' => $residente->id,
        'apartamento_id' => $apartamento->id,
        'empresa' => 'Mensajería Segura',
        'guia' => 'RNF-001',
        'estado' => 'Pendiente',
    ], collect($atributos)->except(['residente', 'apartamento'])->all()));
}

test('vigilante registration form is usable and responsive-ready', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $apartamentoInactivo = Apartamento::factory()->create(['torre' => 'Torre Oculta', 'activo' => false]);
    $residente->apartamentos()->attach($apartamento);
    $residente->apartamentos()->attach($apartamentoInactivo);

    $this->actingAs($vigilante)->get(route('registro-paquetes'))
        ->assertOk()
        ->assertSee('name="viewport"', false)
        ->assertDontSee('Paso 1 de 2')
        ->assertDontSee('Paso 2 de 2')
        ->assertSee('residente_busqueda')
        ->assertSee('apartamento_id')
        ->assertSee('Seleccione primero un residente')
        ->assertSee('const residentes =', false)
        ->assertSee('empresa')
        ->assertSee('guia')
        ->assertSee('foto')
        ->assertSee('detalles')
        ->assertSee('Registrar y notificar al residente')
        ->assertDontSee('Torre Oculta');
});

test('security middleware isolates roles, resident data, inactive accounts and invalid files', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residenteUno = Residente::factory()->create();
    $residenteDos = Residente::factory()->create();
    $usuarioResidente = User::factory()->create(['rol' => 'residente', 'residente_id' => $residenteUno->id]);
    $apartamentoUno = Apartamento::factory()->create();
    $residenteUno->apartamentos()->attach($apartamentoUno);
    $apartamentoDos = Apartamento::factory()->create();
    $residenteDos->apartamentos()->attach($apartamentoDos);
    paqueteRnf(['residente' => $residenteDos, 'apartamento' => $apartamentoDos, 'guia' => 'AJENO-RNF']);

    $this->actingAs($vigilante)->get(route('residentes'))->assertForbidden();
    $this->actingAs($usuarioResidente)->get(route('reportes'))->assertForbidden();
    $this->actingAs($usuarioResidente)->get(route('registro-paquetes'))->assertForbidden();
    $this->actingAs($usuarioResidente)->get(route('mis-paquetes'))->assertDontSee('AJENO-RNF');
    $this->actingAs($usuarioResidente)->post(route('autorizaciones.store', paqueteRnf(['residente' => $residenteDos, 'apartamento' => $apartamentoDos])))->assertForbidden();
    $this->actingAs($vigilante)->from(route('registro-paquetes'))->post(route('paquetes.store'), [
        'residente_id' => $residenteUno->id, 'apartamento_id' => $apartamentoUno->id, 'empresa' => 'Empresa', 'guia' => 'ARCHIVO-RNF',
        'foto' => UploadedFile::fake()->create('archivo.exe', 100, 'application/x-msdownload'),
    ])->assertSessionHasErrors('foto');
    $inactivo = User::factory()->create(['rol' => 'vigilante', 'activo' => false]);
    $this->actingAs($inactivo)->get(route('registro-paquetes'))->assertRedirect(route('login'));
    $this->actingAs($admin)->get(route('reportes'))->assertOk();
    expect(Hash::check('password', User::factory()->create(['password' => Hash::make('password')])->password))->toBeTrue();
});

test('public health check only exposes availability status', function () {
    $this->get(route('health'))
        ->assertOk()
        ->assertExactJson(['status' => 'ok'])
        ->assertDontSee('APP_KEY')
        ->assertDontSee('password');
});

test('package registration and notification complete within three seconds in the test environment', function () {
    Notification::fake();
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create();
    $apartamento = Apartamento::factory()->create();
    $residente->apartamentos()->attach($apartamento);
    User::factory()->create(['rol' => 'residente', 'residente_id' => $residente->id]);

    $inicio = hrtime(true);
    $this->actingAs($vigilante)->post(route('paquetes.store'), [
        'residente_id' => $residente->id, 'apartamento_id' => $apartamento->id, 'empresa' => 'Entrega Rápida', 'guia' => 'RNF-RAPIDO',
    ])->assertRedirect(route('confirmacion'));
    $duracionSegundos = (hrtime(true) - $inicio) / 1_000_000_000;

    expect($duracionSegundos)->toBeLessThan(3.0);
});

test('delivery traceability is server controlled and immutable after delivery', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $otroVigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'Residente RNF']);
    $apartamento = Apartamento::factory()->create();
    $paquete = paqueteRnf(['residente' => $residente, 'apartamento' => $apartamento]);

    $this->actingAs($vigilante)->post(route('entrega.confirmar', $paquete), [
        'tipo_receptor' => 'residente', 'firma' => firmaRnfValida(),
        'entregado_por' => $otroVigilante->id, 'fecha_entrega' => '2000-01-01 00:00:00', 'recibido_por' => 'Nombre manipulado',
    ])->assertRedirect(route('historial'));

    $entregado = $paquete->fresh();
    expect($entregado->estado)->toBe('Entregado')->and($entregado->entregado_por)->toBe($vigilante->id)->and($entregado->recibido_por)->toBe($residente->nombre)->and($entregado->fecha_entrega->isToday())->toBeTrue();
    $trazabilidad = [$entregado->entregado_por, $entregado->recibido_por, $entregado->fecha_entrega->format('c'), $entregado->firma];

    $this->actingAs($otroVigilante)->post(route('entrega.confirmar', $entregado), [
        'tipo_receptor' => 'residente', 'firma' => firmaRnfValida(), 'entregado_por' => $otroVigilante->id,
    ])->assertSessionHas('error', 'Este paquete ya fue entregado.');
    expect([$entregado->fresh()->entregado_por, $entregado->fresh()->recibido_por, $entregado->fresh()->fecha_entrega->format('c'), $entregado->fresh()->firma])->toBe($trazabilidad);
});

test('delivery to an authorized person consumes the authorization with traceability', function () {
    $vigilante = User::factory()->create(['rol' => 'vigilante']);
    $residente = Residente::factory()->create(['nombre' => 'Residente Autorización']);
    $apartamento = Apartamento::factory()->create();
    $paquete = paqueteRnf(['residente' => $residente, 'apartamento' => $apartamento]);
    $autorizacion = AutorizacionRetiro::query()->create([
        'paquete_id' => $paquete->id, 'residente_id' => $residente->id, 'nombre_autorizado' => 'Tercero RNF',
        'documento_autorizado' => '123456', 'relacion' => 'Familiar', 'estado' => AutorizacionRetiro::ACTIVA,
    ]);

    $this->actingAs($vigilante)->post(route('entrega.confirmar', $paquete), ['tipo_receptor' => 'autorizado', 'firma' => firmaRnfValida()])->assertSessionHas('success');

    expect($paquete->fresh()->recibido_por)->toBe('Tercero RNF')->and($paquete->fresh()->entregado_por)->toBe($vigilante->id)->and($paquete->fresh()->fecha_entrega)->not->toBeNull()->and($autorizacion->fresh()->estado)->toBe(AutorizacionRetiro::UTILIZADA);
});
