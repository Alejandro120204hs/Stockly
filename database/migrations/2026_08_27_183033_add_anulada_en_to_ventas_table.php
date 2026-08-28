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
        // Anular NO borra la venta -queda el rastro de que existió y se
        // canceló (igual que el borrado lógico de productos). Null =
        // sigue activa; con fecha = anulada desde ese momento. Los
        // reportes/Dashboard la excluyen de los totales, pero el
        // historial de Ventas la sigue mostrando (marcada).
        Schema::table('ventas', function (Blueprint $table) {
            $table->timestamp('anulada_en')->nullable()->after('estado_facturacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('anulada_en');
        });
    }
};
