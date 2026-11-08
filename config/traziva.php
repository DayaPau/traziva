<?php

return [
    /*
    | Roles del sistema (RF-01). El Coordinador/a es superadministrador.
    */
    'roles' => [
        'coordinador' => 'Coordinador/a de Amanecer',
        'responsable' => 'Responsable de Producción Agrícola',
        'docente'     => 'Docente / Investigador',
        'estudiante'  => 'Estudiante / Practicante',
    ],

    /*
    | Índice de Desempeño del Cultivo (IDC) - FÓRMULA BORRADOR.
    | Pesos y reglas pendientes de validación con el tutor y con Amanecer.
    | "estimacion_cosecha" es informativa (peso 0): las demoras suelen deberse
    | al clima/incidencias, no al manejo (feedback de la reunión con Amanecer).
    */
    'idc' => [
        'version' => 'borrador-0.1',
        'pesos' => [
            'rendimiento'        => 0.40,
            'calidad'            => 0.20,
            'aprovechamiento'    => 0.25,
            'constancia'         => 0.15,
            'estimacion_cosecha' => 0.00,
        ],
        // Días entre registros para considerar "constante" el seguimiento.
        'frecuencia_registro_dias' => 15,
        // Rangos de clasificación (puntaje 0-100).
        'rangos' => ['alto' => 80, 'medio' => 60],
    ],
];
