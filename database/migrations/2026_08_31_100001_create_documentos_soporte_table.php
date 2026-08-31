<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos hacia la DIAN por el lado de los GASTOS (dinero que sale),
 * en espejo a documentos_electronicos (dinero que entra, por ventas):
 *   - documento_soporte: arriendo/servicios pagados a alguien que no
 *     factura electrónicamente -lo genera quien PAGA, no quien cobra.
 *   - nomina_electronica: pago a un empleado.
 * Igual que en Facturación, el CUFE es simulado -no hay integración real
 * con la DIAN todavía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_soporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('numero', 30);
            $table->string('tipo', 30); // documento_soporte | nomina_electronica
            $table->string('beneficiario_nombre');
            $table->string('beneficiario_tipo_documento', 10)->nullable();
            $table->string('beneficiario_numero_documento', 30)->nullable();
            $table->string('cufe');
            $table->decimal('valor_total', 12, 2);
            $table->dateTime('fecha_emision');
            $table->dateTime('anulada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('documento_soporte_gasto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos_soporte')->cascadeOnDelete();
            $table->foreignId('gasto_id')->constrained('gastos')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_soporte_gasto');
        Schema::dropIfExists('documentos_soporte');
    }
};
