<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class EventoFitosanitario extends Model
{
    use Auditable;

    protected $table = 'eventos_fitosanitarios';
    protected $fillable = ['plantacion_id', 'plaga_enfermedad_id', 'fecha_evento', 'nivel_afectacion', 'descripcion', 'registrado_por'];
    protected $casts = ['fecha_evento' => 'date'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
    public function plaga() { return $this->belongsTo(PlagaEnfermedad::class, 'plaga_enfermedad_id'); }
}
