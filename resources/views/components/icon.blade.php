{{-- Íconos de trazo (24x24). Uso: <x-icon name="brote" class="w-5 h-5" /> --}}
@props(['name'])
@php
$paths = [
    'dashboard'    => 'M4 5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5Zm10 0a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V5ZM4 15a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4Zm10 0a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-4Z',
    'brote'        => 'M12 21v-9m0 0c0-3.5-2.5-6-6.5-6 0 3.5 2.5 6 6.5 6Zm0 0c0-3.5 2.5-6.5 6.5-6.5 0 3.5-2.5 6.5-6.5 6.5Z',
    'portapapeles' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h6m-6 4h4',
    'cosecha'      => 'M3 10h18l-2 8.4A2 2 0 0 1 17 20H7a2 2 0 0 1-2-1.6L3 10Zm4 0 3-6m7 6-3-6',
    'medidor'      => 'M5.6 18.4a9 9 0 1 1 12.8 0M12 14l4-4',
    'archivo'      => 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5M12 11v6m0 0-2.5-2.5M12 17l2.5-2.5',
    'usuarios'     => 'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 9v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
    'auditoria'    => 'M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3Zm-3 9 2 2 4-4',
    'catalogo'     => 'M4 6.5C4 5.1 5.1 4 6.5 4H20v13H6.5A2.5 2.5 0 0 0 4 19.5v-13Zm0 13A2.5 2.5 0 0 0 6.5 22H20v-5M8 8h8M8 11h5',
    'ojo'          => 'M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Zm9.5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
    'lapiz'        => 'M16.9 4.1a2.1 2.1 0 0 1 3 3L8 19l-4 1 1-4L16.9 4.1Z',
    'basura'       => 'M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3',
    'salir'      => 'M15 12H3m0 0 4-4m-4 4 4 4M10 5V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-8a1 1 0 0 1-1-1v-1',
    'menu'         => 'M4 6h16M4 12h16M4 18h16',
    'cerrar'       => 'M6 6l12 12M18 6 6 18',
    'mas'          => 'M12 5v14M5 12h14',
    'buscar'       => 'M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z',
    'alerta'       => 'M12 9v4m0 4h.01M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
    'check'        => 'M5 13l4 4L19 7',
    'info'         => 'M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
];
@endphp
<svg {{ $attributes->merge(['class' => 'w-5 h-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['info'] }}"/>
</svg>
