<?php

namespace App\Models;

use Database\Factories\ApartamentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Apartamento extends Model
{
    /** @use HasFactory<ApartamentoFactory> */
    use HasFactory;

    protected $attributes = ['activo' => true];

    protected $fillable = [
        'torre',
        'numero',
        'activo',
    ];

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(Residente::class);
    }
}
