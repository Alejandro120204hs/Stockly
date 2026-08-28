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
 * "Hoy" para Dashboard/Ventas/Gastos es desde que se abrió el turno actual,
 * no medianoche real -así un negocio que cierra pasada la medianoche no ve
 * sus números resetearse ni repartirse entre dos días a mitad de turno.
 */
class TurnoDeCajaTest extends TestCase
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

    public function test_un_turno_que_cruza_la_medianoche_no_reparte_las_ventas_entre_dos_dias(): void
    {
        $this->travelTo('2026-08-20 10:00:00');
        $this->crearUsuarioCliente();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Aguardiente',
            'categoria' => 'Licores',
            'precio_costo' => 10000,
            'precio_venta' => 15000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Aguardiente')->firstOrFail();

        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 10, 'costo' => 10000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 10])->assertOk();

        // Venta de las 10am del día 20.
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // La misma caja sigue abierta -avanzamos a la 1am del día 21 (otro
        // día calendario, mismo turno) y vendemos otra vez.
        $this->travelTo('2026-08-21 01:00:00');
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Un gasto también a la 1am, mismo turno.
        $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => 'Almuerzo de madrugada',
            'monto' => 5000,
            'metodo_pago' => 'efectivo',
        ])->assertOk();

        // El Dashboard, visto justo ahora (1am del 21, turno sigue
        // abierto), debe sumar AMBAS ventas como "de hoy" -son 2 x 15.000
        // = 30.000 en ventas, ganancia bruta 2 x 5.000 = 10.000, neta
        // 10.000 - 5.000 = 5.000.
        $dashboard = $this->get('/cliente/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('data-count="30000"', false);
        $dashboard->assertSee('data-count="10000"', false);
        $dashboard->assertSee('data-count="5000"', false);

        // En Ventas, las dos deben compartir el mismo fechaTurno (el día
        // en que se abrió la caja: el 20), aunque la fecha real de la
        // segunda sea el 21.
        $ventas = $this->get('/cliente/ventas');
        $ventas->assertOk();

        $dataScript = $ventas->getContent();
        preg_match('/id="ventasData"[^>]*>(.*?)<\/script>/s', $dataScript, $matches);
        $ventasData = json_decode($matches[1], true);

        $this->assertCount(2, $ventasData);
        $this->assertSame('2026-08-20', $ventasData[0]['fechaTurno']);
        $this->assertSame('2026-08-20', $ventasData[1]['fechaTurno']);
        // La segunda venta sí ocurrió de verdad el 21 -eso no cambia.
        $this->assertSame('2026-08-21', $ventasData[0]['fecha']);

        // Mismo criterio en Gastos: el de la 1am también queda con
        // fechaTurno = 20, aunque haya pasado el 21.
        $gastos = $this->get('/cliente/gastos');
        $gastos->assertOk();
        preg_match('/id="gastosData"[^>]*>(.*?)<\/script>/s', $gastos->getContent(), $matchesGastos);
        $gastosData = json_decode($matchesGastos[1], true);

        $this->assertCount(1, $gastosData);
        $this->assertSame('2026-08-20', $gastosData[0]['fechaTurno']);
    }

    public function test_sin_caja_abierta_hoy_vuelve_a_ser_medianoche_real(): void
    {
        $this->travelTo('2026-08-21 09:00:00');
        $this->crearUsuarioCliente();

        // Se crea directo con el modelo (sin pasar por el controlador, que
        // sí exigiría una caja abierta para "efectivo") -acá solo importa
        // probar el límite de tiempo ("hoy" sin turno = medianoche real),
        // no la regla de bloqueo de caja abierta, que ya tiene su propio
        // test en GastoCrudTest.
        Gasto::create([
            'usuario_id' => auth()->id(),
            'categoria' => 'servicios',
            'descripcion' => 'Internet',
            'monto' => 8000,
            'metodo_pago' => 'efectivo',
            'fecha' => now(),
        ]);

        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        // Sin caja abierta, "hoy" cae de vuelta a la medianoche real de hoy
        // -este gasto de las 9am del 21 sí cuenta: ganancia neta = 0 (sin
        // ventas) - 8.000 (el gasto) = -8.000.
        $response->assertSee('data-count="-8000"', false);
    }
}
