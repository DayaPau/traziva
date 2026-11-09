<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TipoLabor extends Model
{
    use Auditable;

    protected $table = 'tipos_labor';
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function cultivos() { return $this->belongsToMany(Cultivo::class, 'cultivo_tipo_labor'); }
    public function labores() { return $this->hasMany(LaborCultural::class); }
}
