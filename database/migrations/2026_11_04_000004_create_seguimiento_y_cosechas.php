<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labores_culturales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->constrained('plantaciones')->cascadeOnDelete();
            $t->foreignId('tipo_labor_id')->constrained('tipos_labor');
            $t->date('fecha');
            $t->string('descripcion', 300)->nullable();
            $t->text('observaciones')->nullable();
            $t->char('origen_hash', 40)->nullable()->unique();   // idempotencia de importaciones
            $t->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['plantacion_id', 'fecha']);
        });

        Schema::create('eventos_fitosanitarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->constrained('plantaciones')->cascadeOnDelete();
            $t->foreignId('plaga_enfermedad_id')->constrained('plagas_enfermedades');
            $t->date('fecha_evento');
            $t->enum('nivel_afectacion', ['bajo', 'medio', 'alto'])->nullable();
            $t->text('descripcion')->nullable();
            $t->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('aplicaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->constrained('plantaciones')->cascadeOnDelete();
            $t->date('fecha_aplicacion');
            $t->enum('objetivo', ['fertilizacion', 'control_fitosanitario', 'otro']);
            $t->foreignId('plaga_enfermedad_id')->nullable()->constrained('plagas_enfermedades');
            $t->foreignId('evento_fitosanitario_id')->nullable()->constrained('eventos_fitosanitarios')->nullOnDelete();
            $t->text('observaciones')->nullable();
            $t->char('origen_hash', 40)->nullable()->unique();
            $t->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['plantacion_id', 'fecha_aplicacion']);
        });

        Schema::create('aplicacion_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('aplicacion_id')->constrained('aplicaciones')->cascadeOnDelete();
            $t->foreignId('insumo_id')->constrained('insumos');
            $t->decimal('dosis_cantidad', 10, 2);                          // debe ser > 0
            $t->enum('dosis_unidad', ['g', 'ml', 'kg', 'l']);
            $t->enum('base_tipo', ['por_volumen_mezcla', 'por_planta']);
            $t->decimal('base_valor', 10, 2)->nullable();                  // p. ej. 200 (L de mezcla)
            $t->string('dosis_texto_original', 60)->nullable();
            $t->timestamps();
        });

        Schema::create('climas_referenciales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->nullable()->constrained('plantaciones')->cascadeOnDelete();
            $t->date('fecha');
            $t->enum('condicion', ['soleado', 'nublado', 'lluvioso', 'otro']);
            $t->string('observaciones', 300)->nullable();
            $t->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cosechas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->constrained('plantaciones')->cascadeOnDelete();
            $t->date('fecha_cosecha');
            $t->decimal('cantidad_kg', 10, 3);
            $t->decimal('merma_kg', 10, 3)->default(0);
            // Derivada: cantidad - merma. Nunca editable.
            $t->decimal('cantidad_aprovechable_kg', 10, 3)->storedAs('cantidad_kg - merma_kg');
            $t->foreignId('categoria_calidad_id')->nullable()->constrained('categorias_calidad');
            $t->text('observaciones')->nullable();
            $t->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['plantacion_id', 'fecha_cosecha']);
        });
    }

    public function down(): void
    {
        foreach (['cosechas', 'climas_referenciales', 'aplicacion_detalles', 'aplicaciones', 'eventos_fitosanitarios', 'labores_culturales'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
