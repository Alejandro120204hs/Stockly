<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "efectivo_externo" = pagaste la compra con plata que nunca estuvo en la
 * caja del negocio (ahorros, otro momento) -a diferencia de "efectivo", NO
 * se descuenta de ningún cierre de caja ni requiere una caja abierta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'efectivo_externo', 'digital'])->default('efectivo')->change();
        });
    }

    public function down(): void
    {
        DB::table('compras')->where('metodo_pago', 'efectivo_externo')->update(['metodo_pago' => 'efectivo']);

        Schema::table('compras', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'digital'])->default('efectivo')->change();
        });
    }
};
