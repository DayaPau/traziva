<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Cosecha extends Model
{
    use Auditable;

    protected $table = 'cosechas';
    // cantidad_aprovechable_kg es columna generada: no se asigna.
    protected $fillable = ['plantacion_id', 'fecha_cosecha', 'cantidad_kg', 'merma_kg', 'categoria_calidad_id', 'observaciones', 'registrado_por'];
    protected $casts = ['fecha_cosecha' => 'date', 'cantidad_kg' => 'float', 'merma_kg' => 'float', 'cantidad_aprovechable_kg' => 'float'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
    public function calidad() { return $this->belongsTo(CategoriaCalidad::class, 'categoria_calidad_id'); }
}
