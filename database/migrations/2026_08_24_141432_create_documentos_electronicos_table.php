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
        Schema::create('documentos_electronicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->enum('tipo', ['factura_individual', 'factura_consolidada', 'dee_pos']);
            $table->foreignId('comprador_id')->nullable()->constrained('compradores');
            $table->string('cufe')->nullable();
            $table->string('qr_url')->nullable();
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->dateTime('fecha_emision');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_electronicos');
    }
};
