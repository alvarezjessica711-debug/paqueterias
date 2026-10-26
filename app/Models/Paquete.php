<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Paquete extends Model
{
    protected $fillable = [
        'residente_id',
        'apartamento_id',
        'entregado_por',
        'empresa',
        'destinatario',
        'direccion',
        'guia',
        'foto',
        'detalles',
        'estado',
        'fecha_entrega',
        'recibido_por',
        'codigo_entrega',
        'codigo_entrega_validado_por',
        'codigo_entrega_validado_at',
        'codigo_entrega_usado_en',
        'codigo_entrega_whatsapp_aceptado_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Paquete $paquete): void {
            if (blank($paquete->codigo_entrega)) {
                $paquete->codigo_entrega = self::generarCodigoUnico();
            }
        });
    }

    public static function generarCodigoUnico(): string
    {
        do {
            $codigo = Str::upper(Str::random(10));
        } while (self::query()->where('codigo_entrega', $codigo)->exists());

        return $codigo;
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function apartamento(): BelongsTo
    {
        return $this->casa();
    }

    public function casa(): BelongsTo
    {
        return $this->belongsTo(Apartamento::class, 'apartamento_id');
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por');
    }

    public function autorizacionesRetiro(): HasMany
    {
        return $this->hasMany(AutorizacionRetiro::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(PaqueteAuditoria::class)->latest('created_at');
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
            'codigo_entrega_validado_at' => 'datetime',
            'codigo_entrega_usado_en' => 'datetime',
            'codigo_entrega_whatsapp_aceptado_at' => 'datetime',
        ];
    }
}
