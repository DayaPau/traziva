<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LaborCultural extends Model
{
    use Auditable;

    protected $table = 'labores_culturales';
    protected $fillable = ['plantacion_id', 'tipo_labor_id', 'fecha', 'descripcion', 'observaciones', 'origen_hash', 'registrado_por'];
    protected $casts = ['fecha' => 'date'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
    public function tipo() { return $this->belongsTo(TipoLabor::class, 'tipo_labor_id'); }
    public function registrador() { return $this->belongsTo(User::class, 'registrado_por'); }
}
