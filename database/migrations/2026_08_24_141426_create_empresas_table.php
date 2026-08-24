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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_negocio');
            $table->string('nit')->nullable();
            $table->string('correo_contacto');
            $table->string('telefono_contacto')->nullable();
            $table->enum('estado_suscripcion', ['activo', 'por_vencer', 'vencido', 'suspendido'])->default('activo');
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
