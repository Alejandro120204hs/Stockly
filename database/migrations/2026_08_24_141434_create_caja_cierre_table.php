<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caja_cierre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apertura_id')->constrained('caja_apertura')->cascadeOnDelete();
            $table->decimal('total_efectivo_esperado', 12, 2)->default(0);
            $table->decimal('total_digital', 12, 2)->default(0);
            $table->decimal('total_general', 12, 2)->default(0);
            $table->decimal('conteo_fisico_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->dateTime('fecha_cierre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_cierre');
    }
};
