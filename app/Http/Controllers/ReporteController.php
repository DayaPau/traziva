<?php

namespace App\Http\Controllers;

/** Centro de descargas CSV. Los reportes adicionales se agregan en el Sprint 7. */
class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }
}
