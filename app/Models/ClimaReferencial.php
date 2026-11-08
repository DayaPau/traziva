<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ClimaReferencial extends Model
{
    use Auditable;

    protected $table = 'climas_referenciales';
    protected $fillable = ['plantacion_id', 'fecha', 'condicion', 'observaciones', 'registrado_por'];
    protected $casts = ['fecha' => 'date'];

    public function plantacion() { return $this->belongsTo(Plantacion::class); }
}
