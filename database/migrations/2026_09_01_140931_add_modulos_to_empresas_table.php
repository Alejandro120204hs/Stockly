<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('tiene_facturacion')->default(false)->after('fecha_vencimiento');
            $table->boolean('tiene_nomina')->default(false)->after('tiene_facturacion');
        });

        // Las empresas que ya existían antes de que existiera este
        // interruptor venían usando Facturación/Nómina libremente -no
        // tiene sentido que el default (pensado para empresas NUEVAS de
        // ahora en adelante) se las quite de la nada. Se les deja
        // prendido a las dos, como ya venían.
        DB::table('empresas')->update([
            'tiene_facturacion' => true,
            'tiene_nomina' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['tiene_facturacion', 'tiene_nomina']);
        });
    }
};
