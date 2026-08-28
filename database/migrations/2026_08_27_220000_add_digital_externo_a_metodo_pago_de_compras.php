<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "digital" ahora significa específicamente "pagado con la plata digital
 * QUE RECIBISTE HOY" (ventas digitales confirmadas de esta misma caja) -se
 * descuenta del total esperado digital, igual que "efectivo" se descuenta
 * del total esperado en efectivo. "digital_externo" es plata digital que
 * nunca fue parte de la caja de hoy (ahorros, otro momento) -no se
 * descuenta de nada, mismo rol que "efectivo_externo".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'efectivo_externo', 'digital', 'digital_externo'])->default('efectivo')->change();
        });
    }

    public function down(): void
    {
        DB::table('compras')->where('metodo_pago', 'digital_externo')->update(['metodo_pago' => 'digital']);

        Schema::table('compras', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'efectivo_externo', 'digital'])->default('efectivo')->change();
        });
    }
};
