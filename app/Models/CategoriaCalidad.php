<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Catálogo PROVISIONAL: Amanecer aún no confirma las categorías reales (docs/pendientes.md #1). */
class CategoriaCalidad extends Model
{
    use Auditable;

    protected $table = 'categorias_calidad';
    protected $fillable = ['nombre', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function cosechas() { return $this->hasMany(Cosecha::class); }
}
