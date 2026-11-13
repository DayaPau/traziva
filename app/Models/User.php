<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\Auditable;

class User extends Authenticatable
{
    use Auditable, Notifiable;

    protected $fillable = ['rol_id', 'name', 'email', 'password', 'estado', 'activo_desde', 'activo_hasta'];
    protected $hidden = ['password', 'remember_token'];
    protected array $auditExcluir = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'     => 'hashed',
            'activo_desde' => 'date',
            'activo_hasta' => 'date',
        ];
    }

    public function rol() { return $this->belongsTo(Rol::class, 'rol_id'); }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function esCoordinador(): bool { return $this->tieneRol('coordinador'); }

    /** Quién puede registrar información operativa. */
    public function puedeRegistrar(): bool { return $this->tieneRol('coordinador', 'responsable'); }

    /** Cuenta habilitada: activa y dentro de su ventana de vigencia (accesos temporales). */
    public function estaVigente(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }
        $hoy = today();
        if ($this->activo_desde && $hoy->lt($this->activo_desde)) {
            return false;
        }
        if ($this->activo_hasta && $hoy->gt($this->activo_hasta)) {
            return false;
        }
        return true;
    }
}
