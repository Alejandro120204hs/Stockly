<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Costeo por lote (FIFO): cada compra crea su propio lote con el costo
 * real que se pagó por esas unidades. Al transferir o vender, se
 * descuenta primero del lote más antiguo -así una venta usa el costo
 * real de la unidad física que salió, sin importar que otras unidades
 * del mismo producto se hayan comprado a un precio distinto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('compra_detalle_id')->nullable()->constrained('compra_detalle')->nullOnDelete();
            $table->decimal('costo_unitario', 12, 2);
            $table->unsignedInteger('cantidad_bodega')->default(0);
            $table->unsignedInteger('cantidad_vitrina')->default(0);
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['producto_id', 'fecha']);
        });

        // El stock que ya existía antes de este cambio no tiene lote
        // propio -se le crea uno "heredado" con el precio_costo actual del
        // producto, para que la suma de lotes cuadre con el stock agregado
        // desde el día uno y las ventas/transferencias futuras tengan de
        // dónde descontar.
        $ahora = now();

        DB::table('productos')
            ->join('inventario_bodega', 'inventario_bodega.producto_id', '=', 'productos.id')
            ->where('inventario_bodega.stock', '>', 0)
            ->select('productos.id', 'productos.empresa_id', 'productos.precio_costo', 'inventario_bodega.stock as stock_bodega')
            ->get()
            ->each(function ($producto) use ($ahora) {
                DB::table('lotes_inventario')->insert([
                    'empresa_id' => $producto->empresa_id,
                    'producto_id' => $producto->id,
                    'compra_detalle_id' => null,
                    'costo_unitario' => $producto->precio_costo,
                    'cantidad_bodega' => $producto->stock_bodega,
                    'cantidad_vitrina' => 0,
                    'fecha' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            });

        DB::table('productos')
            ->join('inventario_vitrina', 'inventario_vitrina.producto_id', '=', 'productos.id')
            ->where('inventario_vitrina.stock', '>', 0)
            ->select('productos.id', 'productos.empresa_id', 'productos.precio_costo', 'inventario_vitrina.stock as stock_vitrina')
            ->get()
            ->each(function ($producto) use ($ahora) {
                DB::table('lotes_inventario')->insert([
                    'empresa_id' => $producto->empresa_id,
                    'producto_id' => $producto->id,
                    'compra_detalle_id' => null,
                    'costo_unitario' => $producto->precio_costo,
                    'cantidad_bodega' => 0,
                    'cantidad_vitrina' => $producto->stock_vitrina,
                    'fecha' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_inventario');
    }
};
