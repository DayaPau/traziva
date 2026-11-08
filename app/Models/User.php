<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['rol_id', 'name', 'email', 'password', 'estado', 'activo_desde', 'activo_hasta'];
    protected $hidden = ['password', 'remember_token'];

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

    /** Quién puede crear o modificar información operativa (docente y estudiante solo consultan). */
    public function puedeRegistrar(): bool { return $this->tieneRol('coordinador', 'responsable'); }

    /** Cuenta habilitada: activa y dentro de su ventana de vigencia (accesos temporales). */
    public function estaVigente(): bool
    {
        return $this->motivoBloqueo() === null;
    }

    /** Explica por qué la cuenta no puede ingresar, o null si está habilitada. */
    public function motivoBloqueo(): ?string
    {
        $hoy = today();

        return match (true) {
            $this->estado === 'pendiente' => 'Tu cuenta está pendiente de aprobación por el/la coordinador/a.',
            $this->estado !== 'activo' => 'Tu cuenta está inactiva. Contacta al coordinador/a.',
            $this->activo_desde && $hoy->lt($this->activo_desde) => 'Tu acceso empieza el '.$this->activo_desde->format('d/m/Y').'.',
            $this->activo_hasta && $hoy->gt($this->activo_hasta) => 'Tu acceso venció el '.$this->activo_hasta->format('d/m/Y').'. Contacta al coordinador/a.',
            default => null,
        };
    }

    /** Nombre legible del rol para la interfaz (p. ej. "Responsable de Producción Agrícola"). */
    public function getRolEtiquetaAttribute(): string
    {
        return config('traziva.roles.'.$this->rol?->nombre, $this->rol?->nombre ?? '—');
    }
}
