<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('usuario_apertura_id')->constrained('usuarios');
            $table->foreignId('usuario_cierre_id')->nullable()->constrained('usuarios');
            $table->decimal('base_inicial', 12, 2);
            $table->dateTime('apertura_en');
            $table->dateTime('cierre_en')->nullable();
            $table->decimal('conteo_fisico', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
