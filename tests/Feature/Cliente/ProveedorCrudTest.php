<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Caja;
use App\Models\Cliente\Compra;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Proveedor;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorCrudTest extends TestCase
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

        // La mayoría de estos tests registran compras en efectivo, que
        // ahora requieren una caja abierta (ver App\Http\Controllers\
        // Cliente\CajaController) -se abre una acá para no repetirlo en
        // cada test.
        Caja::create([
            'usuario_apertura_id' => $usuario->id,
            'base_inicial' => 0,
            'apertura_en' => now(),
        ]);

        return $usuario;
    }

    private function datosProveedor(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Licorera Continental S.A.S.',
            'nit' => '900123456',
            'dv' => '7',
            'tipo_persona' => 'juridica',
            'regimen_fiscal' => 'Responsable de IVA',
            'telefono' => '3001234567',
            'correo' => 'contacto@continental.test',
            'direccion' => 'Cra 10 # 20-30',
            'departamento' => 'Antioquia',
            'ciudad' => 'Medellín',
        ], $overrides);
    }

    public function test_crear_proveedor_con_datos_fiscales_completos(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/proveedores', $this->datosProveedor());

        $response->assertOk();
        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Licorera Continental S.A.S.',
            'nit' => '900123456',
            'tipo_persona' => 'juridica',
        ]);
    }

    public function test_no_se_puede_crear_un_proveedor_sin_nit(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/proveedores', $this->datosProveedor(['nit' => '']));

        $response->assertStatus(422);
    }

    public function test_no_se_puede_repetir_el_nit_entre_proveedores_de_la_misma_empresa(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();

        $response = $this->postJson('/cliente/proveedores', $this->datosProveedor(['nombre' => 'Otro nombre']));

        $response->assertStatus(422);
        $this->assertSame(1, Proveedor::count());
    }

    public function test_editar_proveedor_actualiza_sus_datos(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();
        $proveedor = Proveedor::firstOrFail();

        $response = $this->putJson("/cliente/proveedores/{$proveedor->id}", $this->datosProveedor([
            'telefono' => '3009999999',
        ]));

        $response->assertOk();
        $this->assertSame('3009999999', $proveedor->fresh()->telefono);
    }

    public function test_no_se_puede_eliminar_un_proveedor_con_compras_registradas(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();
        $proveedor = Proveedor::firstOrFail();

        Compra::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'con_factura',
            'total' => 50000,
            'usuario_id' => auth()->id(),
            'fecha' => now(),
        ]);

        $response = $this->deleteJson("/cliente/proveedores/{$proveedor->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }

    public function test_se_puede_eliminar_un_proveedor_sin_compras(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();
        $proveedor = Proveedor::firstOrFail();

        $this->deleteJson("/cliente/proveedores/{$proveedor->id}")->assertOk();

        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
    }

    public function test_registrar_compra_con_proveedor_real_queda_asociada_correctamente(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();
        $proveedor = Proveedor::firstOrFail();

        $producto = Producto::create([
            'categoria_id' => \App\Models\Cliente\CategoriaProducto::create(['nombre' => 'Licores'])->id,
            'nombre' => 'Aguardiente',
            'precio_costo' => 10000,
            'precio_venta' => 15000,
            'unidad_medida' => 'Botella',
        ]);

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'proveedor',
            'proveedor_id' => $proveedor->id,
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 5, 'costo' => 9500],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('compras', ['proveedor_id' => $proveedor->id]);
    }

    public function test_no_se_puede_registrar_una_compra_con_el_proveedor_id_de_otra_empresa(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->postJson('/cliente/proveedores', $this->datosProveedor())->assertOk();
        $proveedorB = Proveedor::firstOrFail();

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);
        $producto = Producto::create([
            'categoria_id' => \App\Models\Cliente\CategoriaProducto::create(['nombre' => 'Licores'])->id,
            'nombre' => 'Ron',
            'precio_costo' => 8000,
            'precio_venta' => 12000,
            'unidad_medida' => 'Botella',
        ]);

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'proveedor',
            'proveedor_id' => $proveedorB->id,
            'factura_validada' => false,
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 2, 'costo' => 7500],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Compra::count());
    }
}
