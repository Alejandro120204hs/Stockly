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
        // Solo se llena cuando el cliente pidió factura a su nombre en el
        // momento de la venta -no implica que el documento DIAN ya se haya
        // generado (eso lo hace más adelante el módulo de Facturación vía
        // Factus). Con comprador_id null, la venta es candidata al
        // consolidado del día en vez de a una factura individual.
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('comprador_id')->nullable()->after('usuario_id')->constrained('compradores')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comprador_id');
        });
    }
};
