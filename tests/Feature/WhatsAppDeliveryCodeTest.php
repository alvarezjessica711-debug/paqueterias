<?php

use App\Models\Apartamento;
use App\Models\Paquete;
use App\Models\Residente;
use App\Services\WhatsAppDeliveryCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function paqueteConTelefono(string $telefono): Paquete
{
    $residente = Residente::factory()->create(['telefono' => $telefono]);
    $casa = Apartamento::query()->where('codigo', 'A1')->firstOrFail();
    $residente->casas()->attach($casa);

    return Paquete::query()->create([
        'residente_id' => $residente->id,
        'apartamento_id' => $casa->id,
        'empresa' => 'Mensajería de prueba',
        'destinatario' => $residente->nombre,
        'direccion' => $casa->codigo,
        'estado' => 'Pendiente',
    ]);
}

test('WhatsApp is not reported as sent while the integration is unconfigured', function () {
    config([
        'services.whatsapp.access_token' => null,
        'services.whatsapp.phone_number_id' => null,
        'services.whatsapp.api_version' => null,
        'services.whatsapp.template_name' => null,
    ]);
    Http::preventStrayRequests();
    $paquete = paqueteConTelefono('+573001234567');

    expect(app(WhatsAppDeliveryCodeService::class)->send($paquete))->toBe('not_configured');
    Http::assertNothingSent();
});

test('WhatsApp template receives the generated code and only provider acceptance counts as accepted', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456789',
        'services.whatsapp.api_version' => 'v22.0',
        'services.whatsapp.template_name' => 'codigo_entrega_paquete',
        'services.whatsapp.template_language' => 'es',
    ]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);
    $paquete = paqueteConTelefono('+57 (300) 123-4567');

    expect(app(WhatsAppDeliveryCodeService::class)->send($paquete))->toBe('accepted');
    expect($paquete->fresh()->codigo_entrega_whatsapp_aceptado_at)->not->toBeNull();
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v22.0/123456789/messages')
        && $request->data()['to'] === '573001234567'
        && $request->data()['template']['name'] === 'codigo_entrega_paquete'
        && $request->data()['template']['components'][0]['parameters'][0]['text'] === $paquete->codigo_entrega);
});

test('invalid phone numbers and provider failures do not report a WhatsApp delivery', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456789',
        'services.whatsapp.api_version' => 'v22.0',
        'services.whatsapp.template_name' => 'codigo_entrega_paquete',
    ]);
    Http::preventStrayRequests();
    $sinTelefonoValido = paqueteConTelefono('3001234567');
    expect(app(WhatsAppDeliveryCodeService::class)->send($sinTelefonoValido))->toBe('invalid_phone');
    Http::assertNothingSent();

    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'rejected']], 400)]);
    $providerRejected = paqueteConTelefono('+573001234568');
    expect(app(WhatsAppDeliveryCodeService::class)->send($providerRejected))->toBe('failed');
    expect($providerRejected->fresh()->codigo_entrega_whatsapp_aceptado_at)->toBeNull();
});
