<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Caja;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Venta;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A diferencia de los demás *CrudTest, este helper NO abre una caja
     * automáticamente -este archivo prueba justo el ciclo abrir/cerrar/
     * reabrir, así que cada test decide explícitamente el estado inicial.
     */
    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    /** Crea un producto y le mete stock en vitrina (compra a bodega + transferencia) -requiere caja abierta, igual que en la app real. */
    private function crearProductoConStockEnVitrina(int $cantidad, float $precioCosto = 5000, float $precioVenta = 8000, string $nombre = 'Aguardiente'): Producto
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

    public function test_abrir_caja_crea_el_registro_y_queda_abierta(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/caja/abrir', ['base_inicial' => 150000]);

        $response->assertOk();
        $response->assertJsonPath('caja.baseInicial', 150000);
        $response->assertJsonPath('caja.totalEsperado', 150000);

        $caja = Caja::firstOrFail();
        $this->assertEquals(150000, $caja->base_inicial);
        $this->assertNull($caja->cierre_en);
    }

    public function test_no_se_puede_abrir_una_caja_si_ya_hay_otra_abierta(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();

        $response = $this->postJson('/cliente/caja/abrir', ['base_inicial' => 50000]);

        $response->assertStatus(422);
        $this->assertSame(1, Caja::count());
    }

    public function test_no_se_puede_registrar_venta_en_efectivo_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/caja/' . Caja::firstOrFail()->id . '/cerrar', ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertOk();

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 8000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Venta::count());
    }

    public function test_no_se_puede_registrar_compra_en_efectivo_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Ron',
            'categoria' => 'Licores',
            'precio_costo' => 5000,
            'precio_venta' => 8000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Ron')->firstOrFail();

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 5, 'costo' => 5000]],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Mismo criterio que Compras/Gastos: una venta digital SÍ descuenta del
     * cierre de la caja actual (ver CajaController::calcularTotales()), así
     * que también necesita una abierta -si no, quedaría con caja_id null y
     * ese dinero nunca aparecería en ningún cierre.
     */
    public function test_no_se_puede_registrar_venta_digital_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $producto = $this->crearProductoConStockEnVitrina(5);
        $this->postJson('/cliente/caja/' . Caja::firstOrFail()->id . '/cerrar', ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertOk();

        $response = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Venta::count());
    }

    public function test_cerrar_caja_calcula_esperado_y_diferencia_exacta_sobrante_y_faltante(): void
    {
        $this->crearUsuarioCliente();

        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();

        $exacto = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 100000, 'conteo_digital' => 0]);
        $exacto->assertOk();
        $exacto->assertJsonPath('cierre.totalEsperado', 100000);
        $exacto->assertJsonPath('cierre.diferencia', 0);

        $this->postJson("/cliente/caja/{$caja->id}/reabrir")->assertOk();
        $sobrante = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 105000, 'conteo_digital' => 0]);
        $sobrante->assertJsonPath('cierre.diferencia', 5000);

        $this->postJson("/cliente/caja/{$caja->id}/reabrir")->assertOk();
        $faltante = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 95000, 'conteo_digital' => 0]);
        $faltante->assertJsonPath('cierre.diferencia', -5000);
    }

    public function test_cerrar_caja_suma_ventas_y_resta_compras_en_efectivo(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();

        // Setup: comprar 10 unidades a 5000 c/u en efectivo (-50.000) y
        // pasarlas a vitrina.
        $producto = $this->crearProductoConStockEnVitrina(10, precioCosto: 5000, precioVenta: 8000);

        // Vender 2 en efectivo (+16.000) y 1 en digital confirmada (+8.000,
        // no entra al efectivo pero sí al total general).
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 16000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertOk();

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Esperado = 100.000 + 16.000 (ventas efectivo) - 0 (gastos) -
        // 50.000 (compra efectivo) = 66.000. General = 66.000 + 8.000 = 74.000.
        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 66000, 'conteo_digital' => 8000]);

        $response->assertOk();
        $response->assertJsonPath('cierre.ventasEfectivo', 16000);
        $response->assertJsonPath('cierre.ventasDigital', 8000);
        $response->assertJsonPath('cierre.comprasEfectivo', 50000);
        $response->assertJsonPath('cierre.totalEsperado', 66000);
        $response->assertJsonPath('cierre.totalGeneral', 74000);
        $response->assertJsonPath('cierre.diferencia', 0);
    }

    public function test_compras_con_efectivo_externo_no_descuentan_de_la_caja(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Ron',
            'categoria' => 'Licores',
            'precio_costo' => 5000,
            'precio_venta' => 8000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Ron')->firstOrFail();

        // Pagada con plata que nunca estuvo en la caja -no debe restar del
        // total esperado, así el negocio haya comprado mucho más de lo que
        // hay físicamente en el cajón.
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo_externo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 10, 'costo' => 5000]],
        ])->assertOk();

        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 100000, 'conteo_digital' => 0]);

        $response->assertOk();
        $response->assertJsonPath('cierre.comprasEfectivo', 0);
        $response->assertJsonPath('cierre.totalEsperado', 100000);
        $response->assertJsonPath('cierre.diferencia', 0);
    }

    public function test_compras_con_digital_de_hoy_descuentan_del_total_esperado_digital(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $caja = Caja::firstOrFail();
        $producto = $this->crearProductoConStockEnVitrina(10, precioCosto: 5000, precioVenta: 8000);

        // Vender 1 en digital confirmada (+8.000 al ledger digital).
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Comprar algo pagando con esa misma plata digital de hoy (-5.000).
        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Ron',
            'categoria' => 'Licores',
            'precio_costo' => 5000,
            'precio_venta' => 8000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $ron = Producto::where('nombre', 'Ron')->firstOrFail();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'digital',
            'lineas' => [['producto_id' => $ron->id, 'cantidad' => 1, 'costo' => 5000]],
        ])->assertOk();

        // Esperado efectivo = base(0) - compra efectivo de la vitrina (50.000)
        // = -50.000. Esperado digital = 8.000 - 5.000 = 3.000. General = -47.000.
        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 3000]);

        $response->assertOk();
        $response->assertJsonPath('cierre.ventasDigital', 8000);
        $response->assertJsonPath('cierre.comprasDigital', 5000);
        $response->assertJsonPath('cierre.totalEsperadoDigital', 3000);
        $response->assertJsonPath('cierre.totalEsperado', -50000);
        $response->assertJsonPath('cierre.totalGeneral', -47000);
    }

    /** Mismo criterio que Compras/Gastos: "efectivo"/"digital" (plata de HOY) exigen caja abierta -las variantes "_externo" no. */
    public function test_no_se_puede_pagar_nomina_en_efectivo_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $empleado = \App\Models\Cliente\Empleado::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez', 'tipo_documento' => 'CC', 'numero_documento' => '123',
        ]);

        $response = $this->postJson('/cliente/nomina/documentos', [
            'periodo' => 'Septiembre 2026',
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => 'efectivo',
            'pagos' => [['empleado_id' => $empleado->id, 'monto_pagado' => 30000]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, \App\Models\Cliente\NominaDocumento::count());
    }

    public function test_nomina_en_efectivo_descuenta_del_total_esperado_de_la_caja(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();
        $empleado = \App\Models\Cliente\Empleado::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez', 'tipo_documento' => 'CC', 'numero_documento' => '123',
        ]);

        $this->postJson('/cliente/nomina/documentos', [
            'periodo' => 'Septiembre 2026',
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => 'efectivo',
            'pagos' => [['empleado_id' => $empleado->id, 'monto_pagado' => 30000]],
        ])->assertOk();

        $this->assertSame($caja->id, \App\Models\Cliente\NominaDocumento::firstOrFail()->caja_id);

        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 70000, 'conteo_digital' => 0]);

        $response->assertOk();
        $response->assertJsonPath('cierre.nominaEfectivo', 30000);
        $response->assertJsonPath('cierre.totalEsperado', 70000);
        $response->assertJsonPath('cierre.diferencia', 0);
    }

    public function test_nomina_con_efectivo_externo_no_descuenta_de_la_caja(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();
        $empleado = \App\Models\Cliente\Empleado::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez', 'tipo_documento' => 'CC', 'numero_documento' => '123',
        ]);

        // Pagado con plata que nunca estuvo en la caja -no debe restar del
        // total esperado, y ni siquiera necesita caja abierta para pasar.
        $this->postJson('/cliente/nomina/documentos', [
            'periodo' => 'Septiembre 2026',
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => 'efectivo_externo',
            'pagos' => [['empleado_id' => $empleado->id, 'monto_pagado' => 30000]],
        ])->assertOk();

        $this->assertNull(\App\Models\Cliente\NominaDocumento::firstOrFail()->caja_id);

        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 100000, 'conteo_digital' => 0]);

        $response->assertOk();
        $response->assertJsonPath('cierre.nominaEfectivo', 0);
        $response->assertJsonPath('cierre.totalEsperado', 100000);
        $response->assertJsonPath('cierre.diferencia', 0);
    }

    public function test_nomina_en_digital_descuenta_del_total_esperado_digital(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $caja = Caja::firstOrFail();
        $producto = $this->crearProductoConStockEnVitrina(10, precioCosto: 5000, precioVenta: 8000);
        $empleado = \App\Models\Cliente\Empleado::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez', 'tipo_documento' => 'CC', 'numero_documento' => '123',
        ]);

        // Vender 1 en digital confirmada (+8.000 al ledger digital).
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Pagarle al empleado con esa misma plata digital de hoy (-3.000).
        $this->postJson('/cliente/nomina/documentos', [
            'periodo' => 'Septiembre 2026',
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => 'digital',
            'pagos' => [['empleado_id' => $empleado->id, 'monto_pagado' => 3000]],
        ])->assertOk();

        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 5000]);

        $response->assertOk();
        $response->assertJsonPath('cierre.ventasDigital', 8000);
        $response->assertJsonPath('cierre.nominaDigital', 3000);
        $response->assertJsonPath('cierre.totalEsperadoDigital', 5000);
        $response->assertJsonPath('cierre.diferenciaDigital', 0);
    }

    public function test_cerrar_caja_calcula_la_diferencia_digital_por_separado_de_la_de_efectivo(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();
        $producto = $this->crearProductoConStockEnVitrina(10, precioCosto: 5000, precioVenta: 8000);

        // Ventas digital confirmada por 8.000 -esperado digital = 8.000.
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'digital',
            'pago_confirmado' => true,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Efectivo cuadra exacto (esperado = 100.000 - 50.000 = 50.000),
        // pero en el banco solo llegaron 6.000 de los 8.000 esperados
        // (comisión de Wompi, por ejemplo) -faltante de 2.000 en digital.
        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 50000, 'conteo_digital' => 6000]);

        $response->assertOk();
        $response->assertJsonPath('cierre.diferencia', 0);
        $response->assertJsonPath('cierre.diferenciaDigital', -2000);
        $response->assertJsonPath('cierre.conteoDigital', 6000);
    }

    public function test_ventas_anuladas_no_cuentan_en_el_calculo_de_caja(): void
    {
        $this->crearUsuarioCliente();
        // La base cubre justo la compra (5 x 5.000) para no depender de un
        // conteo físico negativo -eso nunca pasa con efectivo real.
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 25000])->assertOk();
        $caja = Caja::firstOrFail();
        $producto = $this->crearProductoConStockEnVitrina(5);

        $venta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 8000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->json('venta');

        $this->postJson("/cliente/ventas/{$venta['id']}/anular")->assertOk();

        // Sin la venta (anulada), solo queda la compra: esperado =
        // 25.000 - 25.000 = 0.
        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0]);

        $response->assertJsonPath('cierre.ventasEfectivo', 0);
        $response->assertJsonPath('cierre.totalEsperado', 0);
        $response->assertJsonPath('cierre.diferencia', 0);
    }

    public function test_no_se_puede_cerrar_una_caja_ya_cerrada(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $caja = Caja::firstOrFail();
        $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertOk();

        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0]);

        $response->assertStatus(422);
    }

    public function test_no_se_puede_reabrir_una_caja_si_ya_se_abrio_otra_despues(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $primera = Caja::firstOrFail();
        $this->postJson("/cliente/caja/{$primera->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertOk();

        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $response = $this->postJson("/cliente/caja/{$primera->id}/reabrir");

        $response->assertStatus(422);
        $this->assertNotNull($primera->fresh()->cierre_en);
    }

    public function test_reabrir_limpia_conteo_y_diferencia_pero_mantiene_la_base_y_los_movimientos(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();
        $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 90000, 'conteo_digital' => 0])->assertOk();

        $this->assertNotNull($caja->fresh()->conteo_fisico);

        $response = $this->postJson("/cliente/caja/{$caja->id}/reabrir");

        $response->assertOk();
        $response->assertJsonPath('caja.baseInicial', 100000);

        $caja->refresh();
        $this->assertNull($caja->cierre_en);
        $this->assertNull($caja->conteo_fisico);
        $this->assertNull($caja->diferencia);
        $this->assertNull($caja->conteo_digital);
        $this->assertNull($caja->diferencia_digital);
        $this->assertNull($caja->usuario_cierre_id);
        $this->assertEquals(100000, $caja->base_inicial);
    }

    public function test_no_se_puede_ni_cerrar_ni_reabrir_una_caja_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $cajaOtraEmpresa = Caja::firstOrFail();
        $this->postJson("/cliente/caja/{$cajaOtraEmpresa->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertOk();

        $this->crearUsuarioCliente();

        $this->postJson("/cliente/caja/{$cajaOtraEmpresa->id}/cerrar", ['conteo_fisico' => 0, 'conteo_digital' => 0])->assertStatus(404);
        $this->postJson("/cliente/caja/{$cajaOtraEmpresa->id}/reabrir")->assertStatus(404);
    }
}
