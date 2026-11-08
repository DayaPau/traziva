<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdcVariable extends Model
{
    protected $table = 'idc_variables';
    public $timestamps = false;
    protected $fillable = ['idc_calculo_id', 'variable', 'valor_crudo', 'valor_normalizado', 'peso'];
    protected $casts = ['valor_crudo' => 'float', 'valor_normalizado' => 'float', 'peso' => 'float'];

    public function calculo() { return $this->belongsTo(IdcCalculo::class, 'idc_calculo_id'); }
}
