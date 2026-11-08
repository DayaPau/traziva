<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class IdcCalculo extends Model
{
    use Auditable;

    protected $table = 'idc_calculos';
    public $timestamps = false;
    protected $fillable = ['plantacion_id', 'tipo', 'periodo_desde', 'periodo_hasta', 'puntaje', 'categoria', 'datos_faltantes', 'version_formula', 'calculado_por', 'calculado_en'];
    protected $casts = ['periodo_desde' => 'date', 'periodo_hasta' => 'date', 'datos_faltantes' => 'array', 'calculado_en' => 'datetime', 'puntaje' => 'float'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
    public function variables() { return $this->hasMany(IdcVariable::class); }
}
