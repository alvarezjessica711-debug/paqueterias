<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutorizacionRetiro extends Model
{
    public const ACTIVA = 'Activa';

    public const UTILIZADA = 'Utilizada';

    public const CANCELADA = 'Cancelada';

    protected $table = 'autorizaciones_retiro';

    protected $fillable = [
        'paquete_id',
        'residente_id',
        'nombre_autorizado',
        'documento_autorizado',
        'relacion',
        'observacion',
        'estado',
    ];

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
