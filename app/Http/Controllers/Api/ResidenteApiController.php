<?php

namespace App\Http\Controllers\Api;

use App\Models\Residente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidenteApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $residentes = Residente::query()
            ->where('activo', true)
            ->with(['apartamentos' => fn ($query) => $query->where('activo', true)])
            ->when($filters['busqueda'] ?? null, fn ($query, $search) => $query->where('nombre', 'like', "%{$search}%"))
            ->orderBy('nombre')
            ->paginate($filters['per_page'] ?? 15)
            ->through(fn (Residente $resident) => [
                'id' => $resident->id,
                'nombre' => $resident->nombre,
                'apartamentos' => $resident->apartamentos->map(fn ($apartment) => [
                    'id' => $apartment->id, 'torre' => $apartment->torre, 'numero' => $apartment->numero,
                ])->values(),
            ]);

        return response()->json($residentes);
    }
}
