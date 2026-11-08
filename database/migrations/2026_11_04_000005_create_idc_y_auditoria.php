<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idc_calculos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantacion_id')->constrained('plantaciones')->cascadeOnDelete();
            $t->enum('tipo', ['parcial', 'final']);
            $t->date('periodo_desde')->nullable();
            $t->date('periodo_hasta')->nullable();
            $t->decimal('puntaje', 5, 2)->nullable();                      // 0-100; NULL si incompleto
            $t->enum('categoria', ['alto', 'medio', 'bajo', 'incompleto']);
            $t->json('datos_faltantes')->nullable();
            $t->string('version_formula', 30);
            $t->foreignId('calculado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('calculado_en');
            $t->index(['plantacion_id', 'calculado_en']);
        });

        Schema::create('idc_variables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('idc_calculo_id')->constrained('idc_calculos')->cascadeOnDelete();
            $t->enum('variable', ['rendimiento', 'calidad', 'aprovechamiento', 'estimacion_cosecha', 'constancia']);
            $t->decimal('valor_crudo', 12, 3)->nullable();
            $t->decimal('valor_normalizado', 5, 2)->nullable();
            $t->decimal('peso', 4, 3);
        });

        // Solo inserción: el modelo impide UPDATE/DELETE.
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('accion', 30);                                      // crear, modificar, eliminar, importar, exportar, login...
            $t->string('entidad', 60);
            $t->unsignedBigInteger('entidad_id')->nullable();
            $t->json('valores_antes')->nullable();
            $t->json('valores_despues')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['entidad', 'entidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('idc_variables');
        Schema::dropIfExists('idc_calculos');
    }
};
