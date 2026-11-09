<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PlagaEnfermedad extends Model
{
    use Auditable;

    protected $table = 'plagas_enfermedades';
    protected $fillable = ['nombre', 'tipo', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function eventos() { return $this->hasMany(EventoFitosanitario::class); }
}
