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
        Schema::create('pagos_suscripcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('modulo_id')->constrained('modulos');
            $table->decimal('monto', 10, 2);
            $table->string('metodo')->nullable();
            $table->string('id_transaccion_pasarela')->nullable();
            $table->enum('estado', ['pago_recibido', 'activado'])->default('pago_recibido');
            $table->dateTime('fecha_pago');
            $table->dateTime('fecha_activacion')->nullable();
            $table->unsignedBigInteger('usuario_activador_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_suscripcion');
    }
};
