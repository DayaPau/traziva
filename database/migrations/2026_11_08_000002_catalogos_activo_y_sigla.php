<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fase 2: los catálogos se desactivan en lugar de borrarse (conservan el historial),
 * y cada cultivo tiene una sigla editable para generar códigos de plantación (FRU-N06-202509).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['plagas_enfermedades', 'categorias_calidad'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->boolean('activo')->default(true));
        }

        Schema::table('cultivos', fn (Blueprint $t) => $t->string('sigla', 5)->nullable()->after('nombre'));

        // Sigla inicial = 3 primeras letras del nombre (la misma regla que usa el importador).
        foreach (DB::table('cultivos')->get(['id', 'nombre']) as $c) {
            DB::table('cultivos')->where('id', $c->id)
                ->update(['sigla' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($c->nombre)), 0, 3))]);
        }
    }

    public function down(): void
    {
        Schema::table('cultivos', fn (Blueprint $t) => $t->dropColumn('sigla'));
        foreach (['plagas_enfermedades', 'categorias_calidad'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn('activo'));
        }
    }
};
