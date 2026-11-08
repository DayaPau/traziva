<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Aplicacion extends Model
{
    use Auditable;

    protected $table = 'aplicaciones';
    protected $fillable = ['plantacion_id', 'fecha_aplicacion', 'objetivo', 'plaga_enfermedad_id', 'evento_fitosanitario_id', 'observaciones', 'origen_hash', 'registrado_por'];
    protected $casts = ['fecha_aplicacion' => 'date'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
    public function plaga() { return $this->belongsTo(PlagaEnfermedad::class, 'plaga_enfermedad_id'); }
    public function detalles() { return $this->hasMany(AplicacionDetalle::class); }
}
