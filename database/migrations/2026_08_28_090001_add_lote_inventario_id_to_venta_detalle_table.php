<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Para poder devolver el stock al lote correcto cuando se anula una
 * venta -sin esto, anular no sabría a qué lote (y a qué costo original)
 * regresarle las unidades.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalle', function (Blueprint $table) {
            $table->foreignId('lote_inventario_id')->nullable()->after('producto_id')
                ->constrained('lotes_inventario')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('venta_detalle', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lote_inventario_id');
        });
    }
};
