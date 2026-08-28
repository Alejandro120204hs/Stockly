<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->decimal('conteo_digital', 12, 2)->nullable()->after('conteo_fisico');
            $table->decimal('diferencia_digital', 12, 2)->nullable()->after('diferencia');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropColumn(['conteo_digital', 'diferencia_digital']);
        });
    }
};
