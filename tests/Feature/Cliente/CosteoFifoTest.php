<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Producto;
use App\Models\Cliente\Venta;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El costo de cada venta se calcula con el costo REAL del lote del que
 * salió esa unidad (FIFO) -no con un único precio_costo fijo del
 * producto. Así, si compras la misma botella a precios distintos en
 * compras separadas, cada venta refleja su propia ganancia real.
 */
class CosteoFifoTest extends TestCase
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

        return $usuario;
    }

    public function test_cada_venta_usa_el_costo_real_del_lote_del_que_salio(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Amarillo',
            'categoria' => 'Licores',
            'precio_costo' => 45000,
            'precio_venta' => 60000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Amarillo')->firstOrFail();

        // Primera compra: 1 botella a 50.000.
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 50000]],
        ])->assertOk();

        // Segunda compra, después: 1 botella a 55.000.
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 55000]],
        ])->assertOk();

        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 2])->assertOk();

        // Primera venta: debe salir del lote más viejo (50.000) -ganancia 10.000.
        $primeraVenta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $this->assertSame(10000, $primeraVenta->json('venta.ganancia'));

        // Segunda venta: ya solo queda el lote de 55.000 -ganancia 5.000.
        $segundaVenta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $this->assertSame(5000, $segundaVenta->json('venta.ganancia'));

        // Ganancia bruta total del día: 10.000 + 5.000 = 15.000, no
        // 2 x 15.000 (lo que daría usar siempre el mismo precio_costo fijo).
        $dashboard = $this->get('/cliente/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('data-count="15000"', false);
    }

    public function test_una_venta_que_abarca_dos_lotes_distintos_se_reparte_el_costo_correcto(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Amarillo',
            'categoria' => 'Licores',
            'precio_costo' => 45000,
            'precio_venta' => 60000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Amarillo')->firstOrFail();

        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 50000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 55000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 2])->assertOk();

        // Una sola venta de 2 unidades: 1 sale del lote de 50.000 y la
        // otra del lote de 55.000. Ganancia = (60000-50000) + (60000-55000) = 15.000.
        $venta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 120000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertOk();

        $this->assertSame(15000, $venta->json('venta.ganancia'));

        // El recibo/resumen agrupa de vuelta en una sola línea visible
        // para el cliente, aunque internamente haya 2 venta_detalle.
        $this->assertCount(1, $venta->json('venta.lineas'));
        $this->assertSame(2, $venta->json('venta.lineas.0.cantidad'));

        $ventaModel = Venta::firstOrFail();
        $this->assertCount(2, $ventaModel->detalles);

        // El PDF del recibo usa detallesAgrupados(), no detalles() crudo
        // -si no, el cliente vería "Amarillo" repetido dos veces en el
        // recibo en vez de una sola fila con cantidad 2.
        $agrupados = $ventaModel->detallesAgrupados();
        $this->assertCount(1, $agrupados);
        $this->assertSame(2, $agrupados->first()->cantidad);
    }

    public function test_anular_una_venta_devuelve_el_stock_a_su_lote_de_origen(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Amarillo',
            'categoria' => 'Licores',
            'precio_costo' => 45000,
            'precio_venta' => 60000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Amarillo')->firstOrFail();

        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 50000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 1])->assertOk();

        $venta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();
        $ventaId = $venta->json('venta.id');

        $this->postJson("/cliente/ventas/{$ventaId}/anular")->assertOk();

        // Se puede volver a vender esa misma unidad, y sigue costando
        // 50.000 (el mismo lote, no uno nuevo con el precio_costo fijo).
        $segundaVenta = $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $this->assertSame(10000, $segundaVenta->json('venta.ganancia'));
    }
}
