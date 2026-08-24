<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas "detalle" que solo tenían la empresa de forma indirecta (vía
     * su tabla padre: producto, compra, venta, apertura de caja...). Se les
     * agrega empresa_id directo para que el Global Scope multi-tenant pueda
     * filtrar comparando una columna propia, sin necesitar un join/whereHas
     * distinto para cada modelo.
     *
     * @var list<string>
     */
    private array $tablas = [
        'inventario_vitrina',
        'inventario_bodega',
        'compra_detalle',
        'venta_detalle',
        'movimientos_transferencia',
        'pagos_efectivo',
        'pagos_pasarela',
        'caja_cierre',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('empresa_id')->after('id')->constrained('empresas')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
