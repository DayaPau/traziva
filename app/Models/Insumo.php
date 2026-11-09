<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use Auditable;

    protected $table = 'insumos';
    protected $fillable = ['nombre', 'categoria', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function detalles() { return $this->hasMany(AplicacionDetalle::class); }
}
