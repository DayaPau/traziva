<?php

// Mensajes de validación en español (Ecuador). Se agregan reglas según se usen.
return [
    'accepted' => 'Debes aceptar :attribute.',
    'after' => ':Attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute debe ser una fecha igual o posterior a :date.',
    'array' => ':Attribute debe ser una lista.',
    'before' => ':Attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => ':Attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'numeric' => ':Attribute debe estar entre :min y :max.',
        'string' => ':Attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => ':Attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':Attribute no es una fecha válida.',
    'decimal' => ':Attribute debe tener :decimal decimales.',
    'different' => ':Attribute y :other deben ser diferentes.',
    'email' => ':Attribute debe ser un correo electrónico válido.',
    'exists' => 'El valor seleccionado en :attribute no es válido.',
    'file' => ':Attribute debe ser un archivo.',
    'gt' => [
        'numeric' => ':Attribute debe ser mayor que :value.',
    ],
    'gte' => [
        'numeric' => ':Attribute debe ser mayor o igual que :value.',
    ],
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'lt' => [
        'numeric' => ':Attribute debe ser menor que :value.',
    ],
    'lte' => [
        'numeric' => ':Attribute debe ser menor o igual que :value.',
    ],
    'max' => [
        'numeric' => ':Attribute no debe ser mayor que :max.',
        'file' => ':Attribute no debe pesar más de :max kilobytes.',
        'string' => ':Attribute no debe tener más de :max caracteres.',
        'array' => ':Attribute no debe tener más de :max elementos.',
    ],
    'mimes' => ':Attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
        'array' => ':Attribute debe tener al menos :min elementos.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'password' => [
        'letters' => ':Attribute debe contener al menos una letra.',
        'mixed' => ':Attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => ':Attribute debe contener al menos un número.',
        'symbols' => ':Attribute debe contener al menos un símbolo.',
        'uncompromised' => ':Attribute apareció en una filtración de datos. Elige otra.',
    ],
    'required' => ':Attribute es obligatorio.',
    'required_if' => ':Attribute es obligatorio cuando :other es :value.',
    'required_with' => ':Attribute es obligatorio cuando :values está presente.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese valor de :attribute.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'rol_id' => 'rol',
        'activo_hasta' => 'acceso hasta',
        'fecha_cosecha' => 'fecha de cosecha',
        'cantidad_kg' => 'cantidad (kg)',
        'merma_kg' => 'merma (kg)',
        'categoria_calidad_id' => 'categoría de calidad',
        'observaciones' => 'observaciones',
    ],
];
