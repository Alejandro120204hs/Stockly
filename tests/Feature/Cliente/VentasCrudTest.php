<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Caja;
use App\Models\Cliente\Comprador;
use App\Models\Cliente\InventarioVitrina;
use App\Models\Cliente\PagoEfectivo;
use App\Models\Cliente\PagoPasarela;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Venta;
use App\Models\Cliente\VentaDetalle;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasCrudTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $this->actingAs($usuario);

        // La mayoría de estos tests registran ventas/compras en efectivo,
        // que ahora requieren una caja abierta (ver App\Http\Controllers\
        // Cliente\CajaController) -se abre una acá para no repetirlo en
        // cada test; los tests de Caja que sí prueban el bloqueo la abren
        // o cierran explícitamente según lo que necesiten.
        Caja::create([
            'usuario_apertura_id' => $usuario->id,
            'base_inicial' => 0,
            'apertura_en' => now(),
        ]);

        return $usuario;
    }

    /** Crea un producto y le mete stock en vitrina (compra a bodega + transferencia), igual que lo haría el negocio de verdad. */
    private function crearProductoConStockEnVitrina(int $cantidad, float $precioCosto = 10000, float $precioVenta = 15000, string $nombre = 'Aguardiente'): Producto
    {
        $this->postJson('/cliente/inventario/productos', [
            'nombre' => $nombre,
            'categoria' => 'Licores',
            'precio_costo' => $precioCosto,
            'precio_venta' => $precioVenta,
            'unidad_medida' => 'Botella',
        ])->assertOk();

        $producto = Producto::where('nombre', $nombre)->firstOrFail();

        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => $cantidad, 'costo' => $precioCosto]],
        ])->assertOk();

        $this->postJson('/cliente/inventario/transferencias', [
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
        ])->assertOk();

        return $producto;
    }

    public function test_registrar_venta_en_efectivo_descuenta_stock_de_vitrina_y_calcula_el_cambio(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(10);

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 20000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $response->assertOk();
        $this->assertSame(9, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));

        $venta = Venta::firstOrFail();
        $this->assertEquals(15000, $venta->total);
        $this->assertSame('pagada', $venta->estado_pago);

        $pago = PagoEfectivo::where('venta_id', $venta->id)->firstOrFail();
        $this->assertEquals(20000, $pago->monto_recibido);
        $this->assertEquals(5000, $pago->cambio);
    }

    public function test_registrar_venta_congela_el_precio_del_producto_al_momento_de_la_venta(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5, precioCosto: 10000, precioVenta: 15000);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // El precio del catálogo sube DESPUÉS de la venta -el detalle ya
        // guardado no debe cambiar (ver "copiar precios históricos").
        $this->putJson("/cliente/inventario/productos/{$producto->id}", [
            'nombre' => $producto->nombre,
            'categoria' => 'Licores',
            'precio_costo' => 12000,
            'precio_venta' => 20000,
            'unidad_medida' => 'Botella',
        ])->assertOk();

        $detalle = VentaDetalle::where('producto_id', $producto->id)->firstOrFail();
        $this->assertEquals(15000, $detalle->precio_unitario_venta);
        $this->assertEquals(10000, $detalle->precio_unitario_costo);
    }

    public function test_no_se_puede_vender_mas_stock_del_que_hay_en_vitrina(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(3);

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Venta::count());
        $this->assertSame(3, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
    }

    public function test_venta_digital_sin_confirmar_queda_pendiente_y_no_descuenta_bodega(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => false,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $response->assertOk();
        $venta = Venta::firstOrFail();
        $this->assertSame('pendiente', $venta->estado_pago);

        $pago = PagoPasarela::where('venta_id', $venta->id)->firstOrFail();
        $this->assertSame('pendiente', $pago->estado);
        $this->assertNull($pago->fecha_confirmacion);

        // La venta ya descontó vitrina (el pago pendiente no bloquea la
        // salida del producto, igual que el mock original lo mostraba).
        $this->assertSame(3, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
    }

    public function test_venta_digital_confirmada_marca_la_venta_como_pagada(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $venta = Venta::firstOrFail();
        $this->assertSame('pagada', $venta->estado_pago);
        $this->assertSame('confirmado', PagoPasarela::where('venta_id', $venta->id)->value('estado'));
    }

    public function test_la_ganancia_bruta_se_calcula_con_los_precios_historicos_de_la_venta(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5, precioCosto: 10000, precioVenta: 15000);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 30000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertOk();

        $venta = Venta::firstOrFail();
        // 2 unidades * (15000 venta - 10000 costo) = 10000
        $this->assertEquals(10000, $venta->gananciaBruta());
    }

    public function test_pedir_factura_crea_un_comprador_y_lo_asocia_a_la_venta(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'quiere_factura' => true,
            'comprador_tipo_documento' => 'CC',
            'comprador_numero_documento' => '123456789',
            'comprador_nombre' => 'Juan Pérez',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $venta = Venta::firstOrFail();
        $this->assertNotNull($venta->comprador_id);
        $this->assertSame('Juan Pérez', $venta->comprador->nombre);
        $this->assertSame('123456789', $venta->comprador->numero_documento);

        // Todavía no hay integración real con Factus -esto se simula como
        // "ya facturada" solo para poder ver el flujo completo en la
        // interfaz. Cuando se conecte Factus de verdad, este estado debe
        // salir de la respuesta real de la API, no asumirse aquí.
        $this->assertSame('facturada_individual', $venta->estado_facturacion);
    }

    public function test_sin_marcar_quiere_factura_la_venta_no_queda_asociada_a_ningun_comprador(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $venta = Venta::firstOrFail();
        $this->assertNull($venta->comprador_id);
        $this->assertSame(0, Comprador::count());
    }

    public function test_dos_ventas_del_mismo_comprador_reusan_el_mismo_registro_en_vez_de_duplicarlo(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $payloadFactura = [
            'quiere_factura' => true,
            'comprador_tipo_documento' => 'CC',
            'comprador_numero_documento' => '999888777',
            'comprador_nombre' => 'María Gómez',
        ];

        $this->postJson('/cliente/ventas', array_merge($payloadFactura, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]))->assertOk();

        $this->postJson('/cliente/ventas', array_merge($payloadFactura, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]))->assertOk();

        $this->assertSame(1, Comprador::count());
        $this->assertSame(2, Venta::where('comprador_id', Comprador::first()->id)->count());
    }

    public function test_anular_una_venta_devuelve_el_stock_a_vitrina(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertOk();

        $venta = Venta::firstOrFail();
        $this->assertSame(3, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));

        $response = $this->postJson("/cliente/ventas/{$venta->id}/anular");

        $response->assertOk();
        $this->assertSame(5, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
        $this->assertNotNull($venta->fresh()->anulada_en);
    }

    public function test_no_se_puede_anular_dos_veces_la_misma_venta(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $venta = Venta::firstOrFail();

        $this->postJson("/cliente/ventas/{$venta->id}/anular")->assertOk();
        $response = $this->postJson("/cliente/ventas/{$venta->id}/anular");

        $response->assertStatus(422);
        // El stock no se debe devolver dos veces por anular dos veces.
        $this->assertSame(5, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
    }

    public function test_una_venta_anulada_no_cuenta_en_los_totales_del_dashboard(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5, precioCosto: 10000, precioVenta: 15000);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $venta = Venta::firstOrFail();

        $this->postJson("/cliente/ventas/{$venta->id}/anular")->assertOk();

        $response = $this->get('/cliente/dashboard');
        $response->assertOk();
        $response->assertSee('data-count="0"', false);
    }

    public function test_una_venta_anulada_sigue_apareciendo_en_el_historial_de_ventas(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $venta = Venta::firstOrFail();

        $this->postJson("/cliente/ventas/{$venta->id}/anular")->assertOk();

        $this->get('/cliente/ventas')
            ->assertOk()
            ->assertSee('Venta #'.$venta->id);
    }

    public function test_no_se_puede_anular_una_venta_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $ventaAjena = Venta::firstOrFail();

        $this->crearUsuarioCliente();

        $this->postJson("/cliente/ventas/{$ventaAjena->id}/anular")->assertNotFound();
        $this->assertNull($ventaAjena->fresh()->anulada_en);
    }

    public function test_no_se_puede_registrar_una_venta_con_el_producto_id_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $productoOtraEmpresa = $this->crearProductoConStockEnVitrina(5);

        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100000,
            'lineas' => [['producto_id' => $productoOtraEmpresa->id, 'cantidad' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Venta::count());
    }

    public function test_el_recibo_pdf_se_descarga_para_una_venta_propia(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $venta = Venta::firstOrFail();

        $response = $this->get("/cliente/ventas/{$venta->id}/recibo");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_no_se_puede_descargar_el_recibo_de_una_venta_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $ventaAjena = Venta::firstOrFail();

        $this->crearUsuarioCliente();

        $this->get("/cliente/ventas/{$ventaAjena->id}/recibo")->assertNotFound();
    }

    public function test_una_empresa_no_ve_ventas_de_otra_empresa_en_el_index(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $this->crearUsuarioCliente();

        $this->get('/cliente/ventas')
            ->assertOk()
            ->assertDontSee($producto->nombre);

        // Venta::count() ya está filtrado por el EmpresaScope de la
        // empresa B logueada -se consulta sin scopes para confirmar que
        // la venta de la empresa A sigue existiendo, solo invisible.
        $this->assertSame(1, Venta::withoutGlobalScopes()->count());
    }
}
