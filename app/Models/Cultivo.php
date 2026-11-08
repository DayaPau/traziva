<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cultivo extends Model
{
    protected $table = 'cultivos';
    protected $fillable = ['nombre', 'tipo_ciclo', 'vida_productiva_meses', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function plantaciones() { return $this->hasMany(Plantacion::class); }
    public function tiposLabor() { return $this->belongsToMany(TipoLabor::class, 'cultivo_tipo_labor'); }
}
