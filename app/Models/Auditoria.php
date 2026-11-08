<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Tabla de solo inserción: no se permite modificar ni eliminar. */
class Auditoria extends Model
{
    /** Permite silenciar la auditoría fila a fila (p. ej. durante una importación masiva). */
    public static bool $silenciada = false;

    protected $table = 'auditoria';
    public $timestamps = false;
    protected $fillable = ['user_id', 'accion', 'entidad', 'entidad_id', 'valores_antes', 'valores_despues'];
    protected $casts = ['valores_antes' => 'array', 'valores_despues' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La auditoría es de solo inserción.'));
        static::deleting(fn () => throw new LogicException('La auditoría es de solo inserción.'));
    }

    public function user() { return $this->belongsTo(User::class); }
}
