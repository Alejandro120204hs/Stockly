<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para pagos reportados por el cliente (comprobante subido, sin
 * pasarela) -antes SOLO existía la activación manual del admin, que
 * siempre nacía ya 'activado'. Ahora un pago puede nacer 'pago_recibido'
 * (comprobante subido, pendiente de que el admin lo valide) y el admin
 * puede aprobarlo o rechazarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_suscripcion', function (Blueprint $table) {
            $table->string('comprobante_path')->nullable()->after('metodo');
            $table->text('motivo_rechazo')->nullable()->after('estado');
            // Un pago recién reportado por el cliente todavía no tiene
            // vencimiento_nuevo -eso se calcula solo cuando el admin lo
            // aprueba (y depende del vencimiento vigente EN ESE MOMENTO).
            $table->date('vencimiento_nuevo')->nullable()->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            // SQLite emula el ENUM original con un CHECK que no se puede
            // alterar en el lugar -pero acá (tests con RefreshDatabase) la
            // tabla siempre está vacía en este punto, así que es seguro
            // recrear la columna sin perder nada.
            Schema::table('pagos_suscripcion', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
            Schema::table('pagos_suscripcion', function (Blueprint $table) {
                $table->string('estado', 20)->default('pago_recibido');
            });

            return;
        }

        // MySQL sí soporta MODIFY sobre un ENUM en el lugar, sin perder
        // los valores existentes ('pago_recibido'/'activado' siguen
        // siendo válidos, solo se agrega 'rechazado').
        DB::statement("ALTER TABLE pagos_suscripcion MODIFY estado ENUM('pago_recibido','activado','rechazado') NOT NULL DEFAULT 'pago_recibido'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pagos_suscripcion', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
            Schema::table('pagos_suscripcion', function (Blueprint $table) {
                $table->string('estado', 20)->default('pago_recibido');
            });
        } else {
            DB::statement("ALTER TABLE pagos_suscripcion MODIFY estado ENUM('pago_recibido','activado') NOT NULL DEFAULT 'pago_recibido'");
        }

        Schema::table('pagos_suscripcion', function (Blueprint $table) {
            $table->date('vencimiento_nuevo')->nullable(false)->change();
            $table->dropColumn(['comprobante_path', 'motivo_rechazo']);
        });
    }
};
