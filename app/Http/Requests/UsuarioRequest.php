<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Crear y editar usuarios (solo coordinador/a). La contraseña se cambia aparte (ClaveRequest). */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->route('user');

        return $u ? $this->user()->can('update', $u) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $u = $this->route('user');

        $reglas = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($u?->id)],
            'rol_id' => ['required', 'exists:roles,id'],
            'activo_desde' => ['nullable', 'date'],
            // Al crear, el acceso no puede nacer vencido. Al editar se permite una fecha pasada: es la forma de cortar el acceso ya.
            'activo_hasta' => array_filter(['nullable', 'date', 'after_or_equal:activo_desde', $u ? null : 'after_or_equal:today']),
        ];

        if ($u) {
            // "pendiente" solo se conserva si ya lo estaba; no se puede volver a pendiente.
            $reglas['estado'] = ['required', Rule::in($u->estado === 'pendiente' ? ['pendiente', 'activo', 'inactivo'] : ['activo', 'inactivo'])];
        } else {
            $reglas['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }

        return $reglas;
    }

    public function attributes(): array
    {
        return ['activo_desde' => 'acceso desde', 'activo_hasta' => 'acceso hasta', 'estado' => 'estado'];
    }

    public function messages(): array
    {
        return ['activo_hasta.after_or_equal' => 'La fecha de fin del acceso no puede ser anterior al inicio'.($this->route('user') ? '.' : ' ni estar en el pasado.')];
    }
}
