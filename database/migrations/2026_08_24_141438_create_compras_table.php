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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
            $table->foreignId('factura_validada_id')->nullable()->constrained('facturas_proveedor_validadas');
            $table->enum('tipo', ['con_factura', 'sin_factura']);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->dateTime('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
