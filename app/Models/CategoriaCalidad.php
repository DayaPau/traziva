<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaCalidad extends Model
{
    protected $table = 'categorias_calidad';
    protected $fillable = ['nombre', 'orden'];
}
