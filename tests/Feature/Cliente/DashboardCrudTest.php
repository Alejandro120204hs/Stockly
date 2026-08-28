<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Caja;
use App\Models\Cliente\Gasto;
use App\Models\Cliente\Producto;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCrudTest extends TestCase
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
        // cada test.
        Caja::create([
            'usuario_apertura_id' => $usuario->id,
            'base_inicial' => 0,
            'apertura_en' => now(),
        ]);

        return $usuario;
    }

    private function crearProductoConStockEnVitrina(int $cantidad, float $precioCosto = 10000, float $precioVenta = 15000): Producto
    {
        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Aguardiente',
            'categoria' => 'Licores',
            'precio_costo' => $precioCosto,
            'precio_venta' => $precioVenta,
            'unidad_medida' => 'Botella',
        ])->assertOk();

        $producto = Producto::where('nombre', 'Aguardiente')->firstOrFail();

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

    public function test_dashboard_muestra_las_ventas_y_la_ganancia_bruta_reales_del_dia(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5, precioCosto: 10000, precioVenta: 15000);

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 30000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertOk();

        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        // total: 2 * 15000 = 30000, ganancia bruta: 2 * (15000-10000) = 10000
        $response->assertSee('data-count="30000"', false);
        $response->assertSee('data-count="10000"', false);
        $response->assertSee('1 transacción');
    }

    public function test_dashboard_sin_ventas_muestra_ceros_y_el_mensaje_vacio(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        $response->assertSee('0 transacciones');
        $response->assertSee('Todavía no hay ventas registradas hoy.');
    }

    public function test_estado_de_caja_es_cerrada_cuando_nadie_ha_abierto_caja_hoy(): void
    {
        $this->crearUsuarioCliente();
        // crearUsuarioCliente() abre una caja para no bloquear las ventas/
        // compras en efectivo de los demás tests -este test prueba
        // justo el estado "cerrada", así que la cierra primero.
        Caja::first()->update(['cierre_en' => now()]);

        $this->get('/cliente/dashboard')
            ->assertOk()
            ->assertSee('Todavía no la has abierto hoy');
    }

    public function test_la_ganancia_neta_no_mezcla_gastos_de_otra_empresa(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        Gasto::create([
            'usuario_id' => $usuarioB->id,
            'categoria' => 'arriendo',
            'descripcion' => 'Arriendo de agosto',
            'monto' => 999999,
            'metodo_pago' => 'efectivo',
            'fecha' => now(),
        ]);

        $usuarioA = $this->crearUsuarioCliente();
        $producto = $this->crearProductoConStockEnVitrina(5, precioCosto: 10000, precioVenta: 15000);
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Ganancia bruta de A es 5000 (15000-10000) -si el gasto de la
        // empresa B (999999) se colara, la ganancia neta de A saldría
        // negativa. No debe pasar: la consulta a "gastos" tiene que
        // filtrar por empresa_id aunque no use un modelo Eloquent.
        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        $response->assertSee('data-count="5000"', false);
    }

    public function test_caja_abierta_de_otra_empresa_no_afecta_el_estado_de_esta_empresa(): void
    {
        // crearUsuarioCliente() ya abre una caja real para esta empresa B.
        $this->crearUsuarioCliente();

        // Otra empresa (A) -su propia caja (también auto-abierta) se
        // cierra para comprobar que lo que quede abierto en B no se le
        // filtra a A por el Dashboard.
        $this->crearUsuarioCliente();
        Caja::first()->update(['cierre_en' => now()]);

        $this->get('/cliente/dashboard')
            ->assertOk()
            ->assertSee('Todavía no la has abierto hoy');
    }

    public function test_un_admin_sin_empresa_no_puede_entrar_al_dashboard_cliente(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rolAdmin->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        $this->get('/cliente/dashboard')->assertForbidden();
    }
}
