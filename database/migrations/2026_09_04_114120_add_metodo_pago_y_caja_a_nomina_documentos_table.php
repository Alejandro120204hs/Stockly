<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mismo criterio que Gastos/Compras: "efectivo"/"digital" (plata de HOY)
 * descuentan del cierre de la caja actual y quedan asociados a ella;
 * "efectivo_externo"/"digital_externo" son plata que nunca fue parte de
 * esta caja -no descuentan nada y pueden registrarse sin caja abierta.
 * Antes, un pago de nómina en efectivo del cajón de hoy desaparecía del
 * cierre sin que el sistema se enterara (faltante sin explicación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_documentos', function (Blueprint $table) {
            $table->foreignId('caja_id')->nullable()->after('empleado_id')->constrained('cajas')->nullOnDelete();
            $table->string('metodo_pago', 20)->nullable()->after('monto_pagado');
        });
    }

    public function down(): void
    {
        Schema::table('nomina_documentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_id');
            $table->dropColumn('metodo_pago');
        });
    }
};
