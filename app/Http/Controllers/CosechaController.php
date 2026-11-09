<?php

namespace App\Http\Controllers;

use App\Models\Cosecha;
use App\Models\Plantacion;
use Illuminate\Http\Request;

class CosechaController extends Controller
{
    public function store(Request $request, Plantacion $plantacion)
    {
        $d = $request->validate([
            'fecha_cosecha' => 'required|date|before_or_equal:today',
            'cantidad_kg' => 'required|numeric|gt:0',
            'merma_kg' => 'nullable|numeric|min:0|lte:cantidad_kg',
            'categoria_calidad_id' => 'nullable|exists:categorias_calidad,id',
            'observaciones' => 'nullable|string|max:1000',
        ]);
        $d['merma_kg'] = $d['merma_kg'] ?? 0;

        $plantacion->cosechas()->create($d + ['registrado_por' => $request->user()->id]);

        return redirect()->to(route('plantaciones.show', $plantacion).'#cosechas')->with('ok', 'Cosecha registrada. El IDC parcial se recalculó.');
    }
}
