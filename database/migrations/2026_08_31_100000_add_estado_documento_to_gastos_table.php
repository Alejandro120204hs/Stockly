<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Igual que ventas.estado_facturacion -si un gasto ya quedó incluido en
 * un documento soporte/nómina, no debe volver a aparecer como pendiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('estado_documento', 20)->default('sin_reportar')->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('estado_documento');
        });
    }
};
