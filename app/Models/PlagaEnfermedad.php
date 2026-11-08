<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlagaEnfermedad extends Model
{
    protected $table = 'plagas_enfermedades';
    protected $fillable = ['nombre', 'tipo'];
}
