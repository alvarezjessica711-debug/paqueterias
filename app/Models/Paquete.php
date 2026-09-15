<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paquete extends Model
{
    protected $fillable = [
        'residente_id',
        'apartamento_id',
        'entregado_por',
        'empresa',
        'guia',
        'foto',
        'detalles',
        'estado',
        'fecha_entrega',
        'recibido_por',
        'firma',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function apartamento(): BelongsTo
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por');
    }

    public function autorizacionesRetiro(): HasMany
    {
        return $this->hasMany(AutorizacionRetiro::class);
    }

    public function autorizacionActiva(): HasOne
    {
        return $this->hasOne(AutorizacionRetiro::class)
            ->where('estado', AutorizacionRetiro::ACTIVA);
    }

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'datetime',
        ];
    }
}
