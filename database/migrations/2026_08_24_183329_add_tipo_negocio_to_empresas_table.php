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
            // Categoría del negocio (Licorera, Ferretería, etc.), distinto
            // de tipo_persona (natural/jurídica, que es fiscal para DIAN).
            $table->string('tipo_negocio')->nullable()->after('nombre_negocio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('tipo_negocio');
        });
    }
};
