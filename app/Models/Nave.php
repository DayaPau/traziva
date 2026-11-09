<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Nave extends Model
{
    use Auditable;

    protected $table = 'naves';
    protected $fillable = ['numero', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function plantaciones() { return $this->hasMany(Plantacion::class); }
}
