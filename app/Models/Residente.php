<?php

namespace App\Models;

use Database\Factories\ResidenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Residente extends Model
{
    /** @use HasFactory<ResidenteFactory> */
    use HasFactory;

    protected $attributes = ['activo' => true];

    protected $fillable = [
        'nombre',
        'telefono',
        'correo',
        'activo',
    ];

    public function paquetes(): HasMany
    {
        return $this->hasMany(Paquete::class);
    }

    public function autorizacionesRetiro(): HasMany
    {
        return $this->hasMany(AutorizacionRetiro::class);
    }

    public function apartamentos(): BelongsToMany
    {
        return $this->belongsToMany(Apartamento::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
