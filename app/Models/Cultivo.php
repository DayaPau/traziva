<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Cultivo extends Model
{
    use Auditable;

    protected $table = 'cultivos';
    protected $fillable = ['nombre', 'sigla', 'tipo_ciclo', 'vida_productiva_meses', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function plantaciones() { return $this->hasMany(Plantacion::class); }
    public function tiposLabor() { return $this->belongsToMany(TipoLabor::class, 'cultivo_tipo_labor'); }
}
