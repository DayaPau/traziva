<?php

namespace App\Http\Controllers;

/** Centro de descargas CSV. Los reportes adicionales se agregan en la Fase 6. */
class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }
}
