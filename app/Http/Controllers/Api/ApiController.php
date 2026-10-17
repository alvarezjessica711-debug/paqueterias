<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paquete;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function paqueteJson(Paquete $paquete): array
    {
        $paquete->loadMissing(['residente', 'apartamento', 'entregadoPor', 'autorizacionActiva']);

        return [
            'id' => $paquete->id,
            'estado' => $paquete->estado,
            'empresa' => $paquete->empresa,
            'guia' => $paquete->guia,
            'detalles' => $paquete->detalles,
            'foto' => $paquete->foto,
            'residente' => $paquete->residente ? ['id' => $paquete->residente->id, 'nombre' => $paquete->residente->nombre] : null,
            'apartamento' => $paquete->apartamento ? ['id' => $paquete->apartamento->id, 'torre' => $paquete->apartamento->torre, 'numero' => $paquete->apartamento->numero] : null,
            'entrega' => $paquete->estado === 'Entregado' ? [
                'fecha' => $paquete->fecha_entrega?->toISOString(),
                'recibido_por' => $paquete->recibido_por,
                'vigilante' => $paquete->entregadoPor?->name,
            ] : null,
            'autorizacion_activa' => $paquete->autorizacionActiva ? [
                'nombre_autorizado' => $paquete->autorizacionActiva->nombre_autorizado,
                'relacion' => $paquete->autorizacionActiva->relacion,
            ] : null,
            'creado_en' => $paquete->created_at?->toISOString(),
        ];
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
