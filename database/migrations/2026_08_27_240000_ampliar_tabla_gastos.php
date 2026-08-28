<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla "gastos" ya existía desde el diseño original del proyecto, pero
 * nunca se construyó nada encima -acá se ajusta a lo que de verdad se
 * definió con el negocio:
 *   - "categoria_gasto_id" (tabla dinámica categorias_gasto) se reemplaza
 *     por un enum fijo -el negocio solo pidió 4 categorías, sin necesidad
 *     de un "gestionar categorías" como el de productos.
 *   - metodo_pago gana "efectivo_externo"/"digital_externo", mismo patrón
 *     ya usado en compras.
 *   - se agrega caja_id (a qué caja pertenece, igual que ventas/compras) y
 *     usuario_id (quién lo registró) y responsable (quién hizo la compra
 *     en la vida real, ej. "Valentina" -puede ser distinto de quien lo
 *     registra en el sistema).
 *   - "fecha" pasa de date a dateTime, igual que ventas/compras, para
 *     poder mostrar la hora exacta.
 * "empleado_id" se deja intacta -queda lista para cuando se construya un
 * módulo de Empleados/Nómina de verdad más adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['categoria_gasto_id']);
            $table->dropColumn('categoria_gasto_id');

            $table->foreignId('caja_id')->nullable()->after('empresa_id')->constrained('cajas');
            $table->foreignId('usuario_id')->after('caja_id')->constrained('usuarios');
            $table->enum('categoria', ['nomina', 'arriendo', 'servicios', 'otros'])->after('usuario_id');
            $table->string('responsable')->nullable()->after('descripcion');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'efectivo_externo', 'digital', 'digital_externo'])->default('efectivo')->change();
            $table->dateTime('fecha')->change();
            $table->string('descripcion')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['caja_id']);
            $table->dropColumn('caja_id');
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
            $table->dropColumn('categoria');
            $table->dropColumn('responsable');

            $table->foreignId('categoria_gasto_id')->nullable()->constrained('categorias_gasto');
            $table->enum('metodo_pago', ['efectivo', 'digital'])->default('efectivo')->change();
            $table->date('fecha')->change();
            $table->string('descripcion')->nullable()->change();
        });
    }
};
