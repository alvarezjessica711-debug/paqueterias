<?php

namespace App\Models;

use Database\Factories\ApartamentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartamento extends Model
{
    /** @use HasFactory<ApartamentoFactory> */
    use HasFactory;

    protected $attributes = ['activo' => true];

    protected $fillable = [
        'codigo',
        'activo',
    ];

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(Residente::class, 'apartamento_residente', 'apartamento_id', 'residente_id');
    }

    public function paquetes(): HasMany
    {
        return $this->hasMany(Paquete::class, 'apartamento_id');
    }
}
