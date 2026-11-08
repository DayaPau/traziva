<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoLabor extends Model
{
    protected $table = 'tipos_labor';
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function cultivos() { return $this->belongsToMany(Cultivo::class, 'cultivo_tipo_labor'); }
}
