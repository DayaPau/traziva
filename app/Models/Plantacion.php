<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plantacion extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'plantaciones';

    protected $fillable = [
        'codigo', 'cultivo_id', 'nave_id', 'responsable_id', 'variedad', 'num_camas', 'num_plantas',
        'fecha_siembra', 'fecha_siembra_precision', 'fecha_inicio_cosecha_estimada', 'fecha_inicio_cosecha_real',
        'fecha_salida_estimada', 'fecha_salida_real', 'meta_kg_por_cama', 'meta_periodo', 'estado', 'etapa',
        'observaciones', 'es_prueba', 'created_by',
    ];

    protected $casts = [
        'fecha_siembra' => 'date',
        'fecha_inicio_cosecha_estimada' => 'date',
        'fecha_inicio_cosecha_real' => 'date',
        'fecha_salida_estimada' => 'date',
        'fecha_salida_real' => 'date',
        'meta_kg_por_cama' => 'decimal:2',
        'es_prueba' => 'boolean',
    ];

    public function cultivo() { return $this->belongsTo(Cultivo::class); }
    public function nave() { return $this->belongsTo(Nave::class); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function labores() { return $this->hasMany(LaborCultural::class); }
    public function eventos() { return $this->hasMany(EventoFitosanitario::class); }
    public function aplicaciones() { return $this->hasMany(Aplicacion::class); }
    public function climas() { return $this->hasMany(ClimaReferencial::class); }
    public function cosechas() { return $this->hasMany(Cosecha::class); }
    public function idcCalculos() { return $this->hasMany(IdcCalculo::class); }

    public function creador() { return $this->belongsTo(User::class, 'created_by'); }

    public function ultimoIdc()
    {
        return $this->hasOne(IdcCalculo::class)->latestOfMany('calculado_en');
    }

    public const ESTADOS = ['activa' => 'Activa', 'inactiva' => 'Inactiva', 'finalizada' => 'Finalizada'];
    public const ETAPAS = ['crecimiento' => 'Crecimiento', 'floracion' => 'Floración', 'cosecha' => 'Cosecha'];
    public const PERIODOS_META = ['mensual' => 'Mensual', 'anual' => 'Anual', 'ciclo_total' => 'Ciclo total'];
    public const PRECISIONES = ['dia' => 'Día exacto', 'mes' => 'Solo mes', 'anio' => 'Solo año'];

    /** Con labores, aplicaciones, eventos o cosechas no se elimina: se cambia su estado. */
    public function tieneRegistros(): bool
    {
        return $this->labores()->exists() || $this->aplicaciones()->exists()
            || $this->eventos()->exists() || $this->cosechas()->exists();
    }

    /** Fecha de siembra formateada respetando la precisión original del dato. */
    public function getSiembraTextoAttribute(): string
    {
        if (! $this->fecha_siembra) {
            return '—';
        }
        return match ($this->fecha_siembra_precision) {
            'anio' => $this->fecha_siembra->format('Y'),
            'mes'  => $this->fecha_siembra->translatedFormat('M Y'),
            default => $this->fecha_siembra->format('d/m/Y'),
        };
    }
}
