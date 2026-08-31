<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->renameColumn('nombre', 'nombres');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->string('apellidos')->after('nombres');
            $table->string('tipo_documento', 3)->default('CC')->after('apellidos');
            $table->string('numero_documento')->after('tipo_documento');
            $table->date('fecha_retiro')->nullable()->after('cargo');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['apellidos', 'tipo_documento', 'numero_documento', 'fecha_retiro']);
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->renameColumn('nombres', 'nombre');
        });
    }
};
