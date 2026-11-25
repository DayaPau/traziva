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
    | Registro de cuentas (D-16).
    | - Docente y estudiante se registran solos y entran de inmediato (solo consultan).
    | - Coordinador/a y responsable necesitan el código de verificación de su rol, que vive
    |   solo en el .env del servidor (nunca en el repositorio). Si un código está vacío,
    |   ese rol no se ofrece en el registro.
    | - 'limites': máximo de cuentas ACTIVAS por rol (registro y "Usuarios y roles").
    |   Con el cupo lleno, un código filtrado no sirve de nada.
    */
    'registro' => [
        'codigos' => [
            'coordinador' => env('TRAZIVA_CODIGO_COORDINADOR', ''),
            'responsable' => env('TRAZIVA_CODIGO_RESPONSABLE', ''),
        ],
        'limites' => [
            'coordinador' => 1,
            'responsable' => 2,   // Amanecer tiene dos responsables de producción
        ],
    ],

    /*
    | Menú lateral. Fuente única de verdad para la navegación por rol:
    | 'roles' => null significa "todos los roles". 'ruta' => null = módulo aún
    | no construido (se muestra como "Próximamente"). 'activo' = patrón de rutas
    | que resaltan el ítem. La autorización real la hacen middleware + Policies.
    */
    'menu' => [
        ['texto' => 'Dashboard',             'icono' => 'dashboard', 'ruta' => 'dashboard',          'activo' => 'dashboard',      'roles' => null],
        ['texto' => 'Cultivos',              'icono' => 'brote',     'ruta' => 'plantaciones.index', 'activo' => 'plantaciones.*', 'roles' => null],
        ['texto' => 'Labores culturales',    'icono' => 'portapapeles', 'ruta' => 'labores.index',            'activo' => 'labores.*',      'roles' => null],
        ['texto' => 'Cosecha y postcosecha', 'icono' => 'cosecha',   'ruta' => 'cosechas.index',          'activo' => 'cosechas.*',     'roles' => null],
        ['texto' => 'Índice de Desempeño',   'icono' => 'medidor',   'ruta' => 'idc.index',           'activo' => 'idc.*',          'roles' => null],
        ['texto' => 'Catálogos',             'icono' => 'catalogo',  'ruta' => 'catalogos.index',    'activo' => 'catalogos.*',    'roles' => ['coordinador', 'responsable'], 'params' => ['catalogo' => 'cultivos']],
        ['texto' => 'Reportes CSV',         'icono' => 'archivo',   'ruta' => 'reportes.index',     'activo' => 'reportes.*',     'roles' => null],
        ['texto' => 'Usuarios y roles',      'icono' => 'usuarios',  'ruta' => 'usuarios.index',     'activo' => 'usuarios.*',     'roles' => ['coordinador']],
        ['texto' => 'Auditoría básica',      'icono' => 'auditoria', 'ruta' => null,                 'activo' => 'auditoria.*',    'roles' => ['coordinador']],
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
