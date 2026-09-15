<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensajeria extends Model
{
    protected $fillable = [
        'nombre',
        'tipo_servicio',
        'linea_atencion',
        'email',
        'sitio_web',
    ];
}
