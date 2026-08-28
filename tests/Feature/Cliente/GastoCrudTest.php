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

class GastoCrudTest extends TestCase
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

    public function test_registrar_gasto_en_efectivo_de_caja_queda_asociado_a_la_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();

        $response = $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => 'Almuerzo del negocio',
            'responsable' => 'Valentina',
            'monto' => 20000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertOk();
        $response->assertJsonPath('gasto.categoria', 'otros');
        $response->assertJsonPath('gasto.responsable', 'Valentina');
        $response->assertJsonPath('gasto.monto', 20000);

        $gasto = Gasto::firstOrFail();
        $this->assertSame($caja->id, $gasto->caja_id);
        $this->assertEquals(20000, $gasto->monto);
    }

    public function test_no_se_puede_registrar_un_gasto_en_efectivo_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/gastos', [
            'categoria' => 'servicios',
            'descripcion' => 'Pago de luz',
            'monto' => 80000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Gasto::count());
    }

    public function test_gasto_efectivo_externo_no_requiere_caja_abierta_y_no_queda_asociado_a_ninguna(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/gastos', [
            'categoria' => 'arriendo',
            'descripcion' => 'Arriendo de agosto',
            'monto' => 500000,
            'metodo_pago' => 'efectivo_externo',
        ]);

        $response->assertOk();
        $this->assertNull(Gasto::first()->caja_id);
    }

    public function test_gasto_sin_descripcion_o_monto_invalido_falla_la_validacion(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => '',
            'monto' => 10000,
            'metodo_pago' => 'efectivo',
        ])->assertStatus(422);

        $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => 'Algo',
            'monto' => 0,
            'metodo_pago' => 'efectivo',
        ])->assertStatus(422);
    }

    public function test_gastos_en_efectivo_y_digital_se_reflejan_en_el_calculo_de_caja(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 100000])->assertOk();
        $caja = Caja::firstOrFail();

        $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => 'Almuerzo',
            'monto' => 15000,
            'metodo_pago' => 'efectivo',
        ])->assertOk();

        $this->postJson('/cliente/gastos', [
            'categoria' => 'servicios',
            'descripcion' => 'Internet (pagado por transferencia)',
            'monto' => 5000,
            'metodo_pago' => 'digital',
        ])->assertOk();

        // Esperado efectivo = 100.000 - 15.000 = 85.000. Esperado digital
        // = 0 (ventas) - 5.000 (gasto) = -5.000.
        $response = $this->postJson("/cliente/caja/{$caja->id}/cerrar", ['conteo_fisico' => 85000, 'conteo_digital' => 0]);

        $response->assertOk();
        $response->assertJsonPath('cierre.gastosEfectivo', 15000);
        $response->assertJsonPath('cierre.gastosDigital', 5000);
        $response->assertJsonPath('cierre.totalEsperado', 85000);
        $response->assertJsonPath('cierre.totalEsperadoDigital', -5000);
    }

    public function test_gasto_de_hoy_resta_de_la_ganancia_neta_en_el_dashboard(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Aguardiente',
            'categoria' => 'Licores',
            'precio_costo' => 10000,
            'precio_venta' => 15000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Aguardiente')->firstOrFail();

        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 5, 'costo' => 10000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 5])->assertOk();

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        $this->postJson('/cliente/gastos', [
            'categoria' => 'otros',
            'descripcion' => 'Almuerzo del negocio',
            'monto' => 2000,
            'metodo_pago' => 'efectivo',
        ])->assertOk();

        // Ganancia bruta = 15000 - 10000 = 5000. Neta = 5000 - 2000 = 3000.
        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        $response->assertSee('data-count="5000"', false);
        $response->assertSee('data-count="3000"', false);
    }

    public function test_gasto_pagado_aparte_no_resta_de_la_ganancia_neta_del_dashboard(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();

        $this->postJson('/cliente/inventario/productos', [
            'nombre' => 'Aguardiente',
            'categoria' => 'Licores',
            'precio_costo' => 10000,
            'precio_venta' => 15000,
            'unidad_medida' => 'Botella',
        ])->assertOk();
        $producto = Producto::where('nombre', 'Aguardiente')->firstOrFail();

        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 5, 'costo' => 10000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $producto->id, 'cantidad' => 5])->assertOk();

        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertOk();

        // Pagado con plata que no era de la caja de hoy (ej: arriendo desde
        // la cuenta del negocio) -la Ganancia neta del Dashboard es "la
        // ganancia de la caja de hoy", así que esto NO la debe tocar.
        $this->postJson('/cliente/gastos', [
            'categoria' => 'arriendo',
            'descripcion' => 'Arriendo de agosto',
            'monto' => 500000,
            'metodo_pago' => 'efectivo_externo',
        ])->assertOk();

        // Ganancia bruta = 15000 - 10000 = 5000. Neta debe seguir siendo
        // 5000 -el arriendo "aparte" no la toca, aunque sí es un gasto
        // real (eso se verá reflejado en Reportes).
        $response = $this->get('/cliente/dashboard');

        $response->assertOk();
        $response->assertSee('data-count="5000"', false);
    }

    public function test_un_negocio_no_ve_gastos_de_otro_en_el_index(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        Gasto::create([
            'usuario_id' => $usuarioB->id,
            'categoria' => 'nomina',
            'descripcion' => 'Nómina de otro negocio',
            'monto' => 999999,
            'metodo_pago' => 'efectivo_externo',
            'fecha' => now(),
        ]);

        $this->crearUsuarioCliente();

        $response = $this->get('/cliente/gastos');

        $response->assertOk();
        $response->assertDontSee('Nómina de otro negocio');
    }
}
