<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultivos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 80)->unique();
            // Por validar con Amanecer: no se asume ciclo ni vida productiva.
            $t->enum('tipo_ciclo', ['corto', 'semiperenne', 'perenne'])->nullable();
            $t->unsignedInteger('vida_productiva_meses')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('naves', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 10)->unique();
            $t->string('descripcion', 120)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('tipos_labor', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 80)->unique();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('cultivo_tipo_labor', function (Blueprint $t) {
            $t->foreignId('cultivo_id')->constrained('cultivos')->cascadeOnDelete();
            $t->foreignId('tipo_labor_id')->constrained('tipos_labor')->cascadeOnDelete();
            $t->primary(['cultivo_id', 'tipo_labor_id']);
        });

        Schema::create('insumos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 100)->unique();
            $t->enum('categoria', ['fertilizante', 'fitosanitario', 'enmienda_organica', 'otro'])->default('otro');
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('plagas_enfermedades', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 80)->unique();
            $t->enum('tipo', ['plaga', 'enfermedad', 'otro'])->default('otro');
            $t->timestamps();
        });

        Schema::create('categorias_calidad', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 40)->unique();
            $t->unsignedTinyInteger('orden')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['categorias_calidad', 'plagas_enfermedades', 'insumos', 'cultivo_tipo_labor', 'tipos_labor', 'naves', 'cultivos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
