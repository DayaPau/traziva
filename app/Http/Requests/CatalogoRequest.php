<?php

namespace App\Http\Requests;

use App\Support\Catalogos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('gestionar-catalogos');
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return Catalogos::reglas($this->route('catalogo'), $id !== null ? (int) $id : null);
    }

    public function attributes(): array
    {
        return Catalogos::atributos($this->route('catalogo'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sigla')) {
            $this->merge(['sigla' => mb_strtoupper(trim($this->input('sigla')))]);
        }
        if ($this->filled('nombre')) {
            $this->merge(['nombre' => trim(preg_replace('/\s+/u', ' ', $this->input('nombre')))]);
        }
    }
}
