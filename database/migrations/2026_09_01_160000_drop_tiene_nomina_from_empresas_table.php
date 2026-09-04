<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nómina no es un módulo aparte que se prenda/apague por empresa -va
     * incluida siempre dentro de Administración, para todas las empresas.
     * El único interruptor real que existe es Facturación electrónica
     * (Factus); la diferencia con o sin Factus es si el soporte de nómina
     * se puede emitir de verdad a la DIAN o se queda como registro interno.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('tiene_nomina');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('tiene_nomina')->default(true)->after('tiene_facturacion');
        });
    }
};
