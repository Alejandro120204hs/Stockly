<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La licencia pasó a ser por EMPRESA completa, no por módulo (ver
 * App\Http\Controllers\Admin\EmpresaController) -esta tabla ya existía
 * pensada para cobro por módulo pero nunca se usó (0 filas reales), así
 * que se reutiliza en vez de crear una tabla nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_suscripcion', function (Blueprint $table) {
            $table->foreignId('modulo_id')->nullable()->change();
            $table->decimal('monto', 10, 2)->nullable()->change();
            $table->string('plan', 20)->after('modulo_id');
            $table->date('vencimiento_anterior')->nullable()->after('fecha_activacion');
            $table->date('vencimiento_nuevo')->after('vencimiento_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_suscripcion', function (Blueprint $table) {
            $table->dropColumn(['plan', 'vencimiento_anterior', 'vencimiento_nuevo']);
            $table->foreignId('modulo_id')->nullable(false)->change();
            $table->decimal('monto', 10, 2)->nullable(false)->change();
        });
    }
};
