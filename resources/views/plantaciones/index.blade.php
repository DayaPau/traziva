@extends('layouts.app')
@section('titulo', 'Cultivos')
@section('contenido')
<x-page-header titulo="Gestión de cultivos" subtitulo="Plantaciones registradas: cada una es un cultivo en una nave con su fecha de siembra." />
@include('plantaciones._tabla')
@endsection
