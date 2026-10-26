<?php

namespace App\Services;

use App\Models\Paquete;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppDeliveryCodeService
{
    public function send(Paquete $paquete): string
    {
        $paquete->loadMissing('residente');
        $telefono = trim((string) $paquete->residente?->telefono);
        $telefonoNormalizado = preg_replace('/[\s().-]/', '', $telefono) ?? '';

        $token = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $apiVersion = config('services.whatsapp.api_version');
        $template = config('services.whatsapp.template_name');
        $language = config('services.whatsapp.template_language', 'es');

        if (! $token || ! $phoneNumberId || ! $apiVersion || ! $template) {
            return 'not_configured';
        }

        if (! preg_match('/^v\d+\.\d+$/', (string) $apiVersion)) {
            return 'not_configured';
        }

        if (! preg_match('/^\+[1-9]\d{7,14}$/', $telefonoNormalizado)) {
            return 'invalid_phone';
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($telefonoNormalizado, '+'),
                    'type' => 'template',
                    'template' => [
                        'name' => $template,
                        'language' => ['code' => $language],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [[
                                'type' => 'text',
                                'text' => $paquete->codigo_entrega,
                            ]],
                        ]],
                    ],
                ]);

            if ($response->successful() && filled($response->json('messages.0.id'))) {
                $paquete->forceFill(['codigo_entrega_whatsapp_aceptado_at' => now()])->save();

                return 'accepted';
            }
        } catch (\Throwable) {
            // Do not log credentials, phone numbers, message bodies, or provider response payloads.
        }

        Log::warning('No fue posible enviar el código de entrega por WhatsApp.', [
            'paquete_id' => $paquete->id,
        ]);

        return 'failed';
    }
}
