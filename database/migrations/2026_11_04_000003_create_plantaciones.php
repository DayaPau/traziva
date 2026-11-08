<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unidad central de trazabilidad: 1 plantación = 1 cultivo + 1 nave + 1 siembra.
        Schema::create('plantaciones', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 40)->unique();
            $t->foreignId('cultivo_id')->constrained('cultivos');
            $t->foreignId('nave_id')->nullable()->constrained('naves');
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('variedad', 80)->nullable();
            $t->unsignedInteger('num_camas')->nullable();
            $t->unsignedInteger('num_plantas')->nullable();
            $t->date('fecha_siembra')->nullable();
            $t->enum('fecha_siembra_precision', ['dia', 'mes', 'anio'])->default('dia');
            $t->date('fecha_inicio_cosecha_estimada')->nullable();
            $t->date('fecha_inicio_cosecha_real')->nullable();
            $t->date('fecha_salida_estimada')->nullable();
            $t->date('fecha_salida_real')->nullable();
            $t->decimal('meta_kg_por_cama', 10, 2)->nullable();
            $t->enum('meta_periodo', ['mensual', 'anual', 'ciclo_total'])->nullable();
            // Estado administrativo y etapa del cultivo van separados (feedback de Amanecer).
            $t->enum('estado', ['activa', 'inactiva', 'finalizada'])->default('activa');
            $t->enum('etapa', ['crecimiento', 'floracion', 'cosecha'])->nullable();
            $t->text('observaciones')->nullable();
            $t->boolean('es_prueba')->default(false);   // datos sintéticos, nunca mezclar con reales
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantaciones');
    }
};
