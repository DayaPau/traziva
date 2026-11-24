<?php

namespace App\Http\Requests;

use App\Models\Rol;
use App\Services\Usuarios\RegistroCuentas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Registro público (D-16). Reglas:
 *  - Nombre: 3–120 caracteres, solo letras, espacios, apóstrofo, punto y guion.
 *  - Correo: formato válido, único (sin distinguir mayúsculas), máximo 150.
 *  - Contraseña: mínimo 8, mayúsculas y minúsculas, números y símbolos, confirmada, distinta del correo.
 *  - Rol: solo los ofrecidos; coordinador/a y responsable exigen código de verificación y cupo.
 *  - Aceptación del tratamiento de datos personales.
 *  - Campo trampa (honeypot) vacío y límite de 3 intentos cada 10 minutos (ruta) contra bots y fuerza bruta.
 */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // formulario público
    }

    public function rules(): array
    {
        $roles = app(RegistroCuentas::class)->rolesDisponibles()->modelKeys();

        return [
            'name' => ['required', 'string', 'min:3', 'max:120', "regex:/^[\pL\s'.-]+$/u"],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'rol_id' => ['required', Rule::in($roles)],
            'codigo' => ['nullable', 'string', 'max:60'],
            'password' => ['required', 'confirmed', 'different:email', Password::min(8)->mixedCase()->numbers()->symbols()],
            'terminos' => ['accepted'],
            'sitio_web' => ['prohibited'], // campo trampa: invisible para personas, los bots lo llenan
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre', 'rol_id' => 'rol', 'codigo' => 'código de verificación',
            'terminos' => 'tratamiento de datos personales', 'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre solo puede contener letras, espacios, apóstrofos, puntos y guiones.',
            'email.unique' => 'Ya existe una cuenta con ese correo. Si es tuya, ingresa o pide al coordinador/a que restablezca tu contraseña.',
            'password.different' => 'La contraseña no puede ser igual a tu correo.',
            'terminos.accepted' => 'Debes aceptar el tratamiento de tus datos personales para crear la cuenta.',
            'sitio_web.prohibited' => 'No se pudo procesar el registro.',
            'rol_id.in' => 'Elige un rol válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('name'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** Código de verificación y cupo para coordinador/a y responsable. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->hasAny(['rol_id'])) {
                return;
            }
            $registro = app(RegistroCuentas::class);
            $rol = Rol::find($this->input('rol_id'))?->nombre;
            if (! $rol || ! $registro->requiereCodigo($rol)) {
                return;
            }

            if (! $registro->codigoValido($rol, $this->input('codigo'))) {
                $v->errors()->add('codigo', 'El código de verificación no es válido para el rol elegido. Pídelo al coordinador/a.');
            } elseif (! $registro->hayCupo($rol)) {
                $v->errors()->add('rol_id', 'Ya están ocupadas todas las cuentas de ese rol. Contacta al coordinador/a.');
            }
        }];
    }
}
