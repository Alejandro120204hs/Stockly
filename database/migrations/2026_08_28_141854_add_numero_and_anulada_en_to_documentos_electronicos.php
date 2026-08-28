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
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            // Número visible del documento, ej. FI-2026-001, FC-2026-002, DEE-2026-003.
            // Consecutivo global por empresa y año (no por tipo), generado al
            // emitir -un único contador evita huecos confusos en el historial.
            $table->string('numero', 30)->nullable()->after('id');

            // Anulación lógica: no se borra el documento, queda como rastro
            // (igual que ventas anuladas). Si anulada_en != null → anulada.
            $table->dateTime('anulada_en')->nullable()->after('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_electronicos', function (Blueprint $table) {
            $table->dropColumn(['numero', 'anulada_en']);
        });
    }
};
