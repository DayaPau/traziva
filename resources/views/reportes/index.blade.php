@extends('layouts.app')
@section('titulo', 'Reportes CSV')
@section('contenido')
<x-page-header titulo="Reportes CSV" subtitulo="Archivos UTF-8 separados por punto y coma (;), listos para abrir en Excel." />

<div class="grid gap-4 md:grid-cols-2">
    <x-card titulo="Plantaciones" subtitulo="Código, cultivo, nave, siembra, camas, plantas, estado y número de registros.">
        <a href="{{ route('plantaciones.exportar') }}" class="btn-primary"><x-icon name="archivo" class="w-4 h-4" /> Descargar CSV</a>
    </x-card>
    <x-card titulo="Labores, aplicaciones, cosechas e IDC" subtitulo="Se agregarán en la Fase 6.">
        <x-badge>Próximamente</x-badge>
    </x-card>
</div>
@endsection
