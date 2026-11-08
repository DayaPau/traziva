<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 40)->unique();      // coordinador, responsable, docente, estudiante
            $t->string('descripcion', 200)->nullable();
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rol_id')->constrained('roles');
            $t->string('name', 120);
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->enum('estado', ['activo', 'inactivo'])->default('activo');
            $t->date('activo_desde')->nullable();
            $t->date('activo_hasta')->nullable();      // accesos temporales (estudiantes)
            $t->rememberToken();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
