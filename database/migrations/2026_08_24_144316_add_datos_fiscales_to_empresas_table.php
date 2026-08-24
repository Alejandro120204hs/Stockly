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
        Schema::table('empresas', function (Blueprint $table) {
            // Datos fiscales típicos para facturación electrónica DIAN
            // (vía Factus). Nullable porque una empresa puede registrarse
            // en Stockly antes de completar su configuración de facturación;
            // pendiente confirmar contra la documentación real de Factus si
            // falta o sobra algo de esto.
            $table->string('dv', 2)->nullable()->after('nit');
            $table->enum('tipo_persona', ['natural', 'juridica'])->nullable()->after('dv');
            $table->string('regimen_fiscal')->nullable()->after('tipo_persona');
            $table->string('direccion')->nullable()->after('telefono_contacto');
            $table->string('departamento')->nullable()->after('direccion');
            $table->string('ciudad')->nullable()->after('departamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['dv', 'tipo_persona', 'regimen_fiscal', 'direccion', 'departamento', 'ciudad']);
        });
    }
};
