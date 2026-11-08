<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro público con aprobación: quien se registra queda "pendiente"
 * hasta que el/la coordinador/a lo aprueba (ver docs/decisiones.md, D-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->enum('estado', ['pendiente', 'activo', 'inactivo'])->default('activo')->change();
        });
    }

    public function down(): void
    {
        // Los pendientes pasan a inactivos antes de quitar el valor del enum.
        \Illuminate\Support\Facades\DB::table('users')->where('estado', 'pendiente')->update(['estado' => 'inactivo']);

        Schema::table('users', function (Blueprint $t) {
            $t->enum('estado', ['activo', 'inactivo'])->default('activo')->change();
        });
    }
};
