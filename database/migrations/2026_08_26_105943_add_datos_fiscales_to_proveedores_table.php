<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mismos datos fiscales que ya se le piden a `empresas` -un proveedor
     * es otro tercero ante la DIAN, y para que la contabilidad (deducción
     * de costos, exógena) sea válida hace falta identificarlo bien, no
     * solo con el nombre.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('dv', 2)->nullable()->after('nit');
            $table->enum('tipo_persona', ['natural', 'juridica'])->nullable()->after('dv');
            $table->string('regimen_fiscal')->nullable()->after('tipo_persona');
            $table->string('telefono')->nullable()->after('regimen_fiscal');
            $table->string('correo')->nullable()->after('telefono');
            $table->string('direccion')->nullable()->after('correo');
            $table->string('departamento')->nullable()->after('direccion');
            $table->string('ciudad')->nullable()->after('departamento');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['dv', 'tipo_persona', 'regimen_fiscal', 'telefono', 'correo', 'direccion', 'departamento', 'ciudad']);
        });
    }
};
