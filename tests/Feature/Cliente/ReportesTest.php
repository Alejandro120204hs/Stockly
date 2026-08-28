<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Gasto;
use App\Models\Cliente\Producto;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reglas de negocio verificadas con el dueño del producto para el módulo
 * de Reportes:
 *
 *  1. Multi-tenant: los reportes de una empresa JAMÁS incluyen datos de otra.
 *  2. Ganancia neta = ganancia BRUTA (precio_venta − precio_costo HISTÓRICO,
 *     FIFO) − TODOS los gastos. No es "ingresos − gastos".
 *  3. Gastos "aparte" (efectivo_externo / digital_externo) SÍ restan en
 *     Reportes, a diferencia del Dashboard que los ignora.
 *  4. "Día" = turno de caja. Una venta de la 1am de un turno que sigue
 *     abierto desde ayer no se reparte entre dos días en la gráfica.
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id'     => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    private function reportesData(string $periodo = 'mes'): array
    {
        $html = $this->get('/cliente/reportes')->assertOk()->getContent();
        preg_match('/id="reportesData"[^>]*>(.*?)<\/script>/s', $html, $m);
        $all = json_decode($m[1], true);

        return $all[$periodo];
    }

    // -------------------------------------------------------------------------
    // 1. Multi-tenant
    // -------------------------------------------------------------------------

    public function test_los_reportes_de_una_empresa_no_incluyen_ventas_de_otra(): void
    {
        // Empresa B registra una venta de 120.000.
        $this->travelTo('2026-08-01 10:00:00');
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Ron B', 'categoria' => 'Licores',
            'precio_costo' => 40000, 'precio_venta' => 120000, 'unidad_medida' => 'Botella',
        ])->assertOk();
        $prodB = Producto::where('nombre', 'Ron B')->firstOrFail();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prodB->id, 'cantidad' => 1, 'costo' => 40000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $prodB->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 120000,
            'lineas' => [['producto_id' => $prodB->id, 'cantidad' => 1]],
        ])->assertOk();

        // Empresa A no debe ver esas ventas en sus reportes.
        $this->crearUsuarioCliente();
        $data = $this->reportesData('mes');

        $this->assertEquals(0, $data['ingresos']);
        $this->assertEquals(0, $data['gananciaBruta']);
        $this->assertSame(0, $data['cantidadVentas']);
    }

    public function test_los_reportes_de_una_empresa_no_incluyen_gastos_de_otra(): void
    {
        $this->travelTo('2026-08-01 09:00:00');

        // Empresa B registra un gasto.
        $this->crearUsuarioCliente();
        Gasto::create([
            'usuario_id'  => auth()->id(),
            'categoria'   => 'nomina',
            'descripcion' => 'Sueldo empleado B',
            'monto'       => 500000,
            'metodo_pago' => 'efectivo',
            'fecha'       => now(),
        ]);

        // Empresa A no debe ver ese gasto.
        $this->crearUsuarioCliente();
        $data = $this->reportesData('mes');

        $this->assertEquals(0, $data['gastos']);
        $this->assertEquals(0, $data['gananciaNeta']);
    }

    // -------------------------------------------------------------------------
    // 2. Ganancia neta = ganancia BRUTA (FIFO) − gastos
    // -------------------------------------------------------------------------

    public function test_ganancia_neta_usa_ganancia_bruta_no_ingresos(): void
    {
        $this->travelTo('2026-08-01 10:00:00');
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Aguardiente', 'categoria' => 'Licores',
            'precio_costo' => 45000, 'precio_venta' => 60000, 'unidad_medida' => 'Botella',
        ])->assertOk();
        $prod = Producto::where('nombre', 'Aguardiente')->firstOrFail();

        // Compra a 50.000 (primer lote) y a 55.000 (segundo lote).
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1, 'costo' => 50000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1, 'costo' => 55000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $prod->id, 'cantidad' => 2])->assertOk();

        // Dos ventas: lote 50k → ganancia 10k; lote 55k → ganancia 5k.
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();

        $data = $this->reportesData('mes');

        // Ingresos = 2 × 60k = 120k.
        $this->assertEquals(120000, $data['ingresos']);
        // Ganancia bruta = 10k + 5k = 15k (precios históricos FIFO).
        $this->assertEquals(15000, $data['gananciaBruta']);
        // Sin gastos, ganancia neta = ganancia bruta.
        $this->assertEquals(15000, $data['gananciaNeta']);
        // El campo NO es igual a los ingresos — esa era la confusión anterior.
        $this->assertNotEquals($data['ingresos'], $data['gananciaNeta']);
    }

    // -------------------------------------------------------------------------
    // 3. Gastos "aparte" (efectivo_externo / digital_externo) sí restan
    // -------------------------------------------------------------------------

    public function test_gastos_aparte_restan_en_la_ganancia_neta_de_reportes(): void
    {
        $this->travelTo('2026-08-01 09:00:00');
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        // Gasto "aparte" — plata que no salió de la caja del día.
        $this->postJson('/cliente/gastos', [
            'categoria'   => 'arriendo',
            'descripcion' => 'Canon mensual',
            'monto'       => 300000,
            'metodo_pago' => 'efectivo_externo',
        ])->assertOk();

        // Gasto "aparte" digital.
        $this->postJson('/cliente/gastos', [
            'categoria'   => 'servicios',
            'descripcion' => 'Internet',
            'monto'       => 80000,
            'metodo_pago' => 'digital_externo',
        ])->assertOk();

        $data = $this->reportesData('mes');

        // Ambos gastos (300k + 80k = 380k) deben aparecer en el total.
        $this->assertEquals(380000, $data['gastos']);
        // Sin ventas, ganancia neta = 0 − 380k = −380k.
        $this->assertEquals(-380000, $data['gananciaNeta']);
    }

    // -------------------------------------------------------------------------
    // 4. Turno de caja — la gráfica no parte un turno que cruza medianoche
    // -------------------------------------------------------------------------

    public function test_la_grafica_atribuye_ventas_al_dia_de_apertura_de_la_caja(): void
    {
        $this->travelTo('2026-08-05 22:00:00');
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Cerveza', 'categoria' => 'Cervezas',
            'precio_costo' => 3000, 'precio_venta' => 5000, 'unidad_medida' => 'Lata',
        ])->assertOk();
        $prod = Producto::where('nombre', 'Cerveza')->firstOrFail();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 2, 'costo' => 3000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $prod->id, 'cantidad' => 2])->assertOk();

        // Venta de las 10pm del día 5 (mismo turno).
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 5000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();

        // Misma caja, avanzamos al 6 (madrugada).
        $this->travelTo('2026-08-06 01:00:00');
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 5000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();

        // Pedimos reportes desde el mes (que incluye ambas fechas).
        $data = $this->reportesData('mes');

        // Ambas ventas deben aparecer — son del mismo turno.
        $this->assertEquals(10000, $data['ingresos']);
        $this->assertSame(2, $data['cantidadVentas']);

        // En la gráfica de barras, ambas ventas deben caer el día 5
        // (fecha de apertura de la caja), no el día 6.
        //
        // El reporte se pide estando en Aug 6, por lo que la gráfica del mes
        // tiene barras para Aug 1-6. Las dos ventas son del turno del día 5,
        // así que TODA la suma debe estar en la penúltima barra (Aug 5),
        // y la última (Aug 6) debe estar en cero.
        $bars = $data['graficaBars'];
        $penultima = $bars[count($bars) - 2]; // Aug 5
        $ultima     = $bars[count($bars) - 1]; // Aug 6

        $this->assertEquals(10000, $penultima['total'], 'Ambas ventas caen el día 5 (apertura del turno)');
        $this->assertEquals(0,     $ultima['total'],    'La barra del día 6 debe estar vacía');
    }
}
