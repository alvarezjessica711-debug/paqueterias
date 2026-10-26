<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PaqueteAuditoria extends Model
{
    public $timestamps = false;

    protected $table = 'paquete_auditorias';

    protected $fillable = [
        'paquete_id',
        'user_id',
        'accion',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los registros de auditoría son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los registros de auditoría son inmutables.'));
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
