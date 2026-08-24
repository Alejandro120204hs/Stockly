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
        Schema::table('movimientos_transferencia', function (Blueprint $table) {
            // Hasta ahora la tabla asumía siempre bodega -> vitrina de forma
            // implícita. Este campo lo deja explícito y permite registrar
            // también el caso inverso (ej. devolver algo a bodega).
            $table->enum('direccion', ['bodega_a_vitrina', 'vitrina_a_bodega'])
                ->default('bodega_a_vitrina')
                ->after('producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_transferencia', function (Blueprint $table) {
            $table->dropColumn('direccion');
        });
    }
};
