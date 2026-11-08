<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AplicacionDetalle extends Model
{
    protected $table = 'aplicacion_detalles';
    protected $fillable = ['aplicacion_id', 'insumo_id', 'dosis_cantidad', 'dosis_unidad', 'base_tipo', 'base_valor', 'dosis_texto_original'];

    public function aplicacion() { return $this->belongsTo(Aplicacion::class); }
    public function insumo() { return $this->belongsTo(Insumo::class); }
}
