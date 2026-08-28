<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Caja;
use App\Models\Cliente\CategoriaProducto;
use App\Models\Cliente\Compra;
use App\Models\Cliente\InventarioBodega;
use App\Models\Cliente\InventarioVitrina;
use App\Models\Cliente\Producto;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioCrudTest extends TestCase
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
        // cada test; los que sí prueban el bloqueo o la validación de
        // metodo_pago la abren o simplemente no dependen de ella.
        Caja::create([
            'usuario_apertura_id' => $usuario->id,
            'base_inicial' => 0,
            'apertura_en' => now(),
        ]);

        return $usuario;
    }

    private function crearProducto(string $nombre = 'Aguardiente', string $categoria = 'Licores'): Producto
    {
        $response = $this->postJson('/cliente/inventario/productos', [
            'nombre' => $nombre,
            'categoria' => $categoria,
            'precio_costo' => 10000,
            'precio_venta' => 15000,
            'unidad_medida' => 'Botella',
        ]);

        $response->assertOk();

        return Producto::where('nombre', $nombre)->firstOrFail();
    }

    public function test_crear_producto_lo_guarda_con_inventario_en_cero_en_vitrina_y_bodega(): void
    {
        $this->crearUsuarioCliente();

        $producto = $this->crearProducto();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => 'Aguardiente']);
        $this->assertSame(0, $producto->stockVitrina());
        $this->assertSame(0, $producto->stockBodega());
    }

    public function test_crear_producto_crea_la_categoria_si_no_existia(): void
    {
        $this->crearUsuarioCliente();

        $this->crearProducto('Ron Viejo', 'Ron');

        $this->assertDatabaseHas('categorias_producto', ['nombre' => 'Ron']);
    }

    public function test_editar_producto_actualiza_sus_datos(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();

        $response = $this->putJson("/cliente/inventario/productos/{$producto->id}", [
            'nombre' => 'Aguardiente Antioqueño',
            'categoria' => 'Licores',
            'precio_costo' => 11000,
            'precio_venta' => 16000,
            'unidad_medida' => 'Botella',
        ]);

        $response->assertOk();
        $this->assertSame('Aguardiente Antioqueño', $producto->fresh()->nombre);
        $this->assertEquals(11000, $producto->fresh()->precio_costo);
    }

    public function test_eliminar_producto_es_borrado_logico_no_fisico(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();

        $this->deleteJson("/cliente/inventario/productos/{$producto->id}")->assertOk();

        $this->assertSoftDeleted($producto);
        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
    }

    public function test_no_se_puede_crear_una_categoria_duplicada_ignorando_mayusculas(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/inventario/categorias', ['nombre' => 'Bebidas'])->assertOk();

        $response = $this->postJson('/cliente/inventario/categorias', ['nombre' => 'bebidas']);

        $response->assertStatus(422);
        $this->assertSame(1, CategoriaProducto::count());
    }

    public function test_no_se_puede_eliminar_una_categoria_que_tiene_productos(): void
    {
        $this->crearUsuarioCliente();
        $this->crearProducto('Aguardiente', 'Licores');

        $response = $this->deleteJson('/cliente/inventario/categorias', ['nombre' => 'Licores']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('categorias_producto', ['nombre' => 'Licores']);
    }

    public function test_se_puede_eliminar_una_categoria_sin_productos(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/inventario/categorias', ['nombre' => 'Vacía'])->assertOk();

        $this->deleteJson('/cliente/inventario/categorias', ['nombre' => 'Vacía'])->assertOk();

        $this->assertDatabaseMissing('categorias_producto', ['nombre' => 'Vacía']);
    }

    public function test_renombrar_una_categoria_actualiza_el_nombre_de_todos_sus_productos(): void
    {
        $this->crearUsuarioCliente();
        $this->crearProducto('Aguardiente', 'Licores');

        $response = $this->putJson('/cliente/inventario/categorias', [
            'nombre_actual' => 'Licores',
            'nombre_nuevo' => 'Aguardientes y rones',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('categorias_producto', ['nombre' => 'Aguardientes y rones']);
        $this->assertDatabaseMissing('categorias_producto', ['nombre' => 'Licores']);
    }

    public function test_no_se_puede_renombrar_una_categoria_al_nombre_de_otra_ya_existente(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/inventario/categorias', ['nombre' => 'Licores'])->assertOk();
        $this->postJson('/cliente/inventario/categorias', ['nombre' => 'Cigarrillos'])->assertOk();

        $response = $this->putJson('/cliente/inventario/categorias', [
            'nombre_actual' => 'Cigarrillos',
            'nombre_nuevo' => 'Licores',
        ]);

        $response->assertStatus(422);
    }

    public function test_registrar_compra_informal_suma_stock_a_bodega_nunca_a_vitrina(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 12, 'costo' => 9500],
            ],
        ]);

        $response->assertOk();
        $this->assertSame(12, InventarioBodega::where('producto_id', $producto->id)->value('stock'));
        $this->assertSame(0, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
        $this->assertSame(1, Compra::count());
    }

    public function test_registrar_compra_guarda_el_metodo_de_pago_digital(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'digital',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'costo' => 9500],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('compra.metodo', 'digital');
        $this->assertSame('digital', Compra::first()->metodo_pago);
    }

    public function test_no_se_puede_registrar_una_compra_con_digital_de_hoy_sin_caja_abierta(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();
        // "digital" ahora significa "con lo digital que recibiste hoy",
        // así que también depende de que haya una caja abierta.
        Caja::first()->update(['cierre_en' => now()]);

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'digital',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'costo' => 9500],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Compra::count());
    }

    public function test_compra_con_digital_externo_no_requiere_caja_abierta_y_no_queda_asociada_a_ninguna(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();
        Caja::first()->update(['cierre_en' => now()]);

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'digital_externo',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'costo' => 9500],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('compra.metodo', 'digital_externo');
        $this->assertNull(Compra::first()->caja_id);
    }

    public function test_compra_con_efectivo_externo_no_requiere_caja_abierta_y_no_queda_asociada_a_ninguna(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();
        // La caja que abre crearUsuarioCliente() se cierra a propósito acá
        // -efectivo_externo es plata que nunca estuvo en la caja, así que
        // debe poder registrarse aunque no haya ninguna abierta.
        Caja::first()->update(['cierre_en' => now()]);

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo_externo',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'costo' => 9500],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('compra.metodo', 'efectivo_externo');
        $this->assertNull(Compra::first()->caja_id);
    }

    public function test_no_se_puede_registrar_una_compra_con_metodo_de_pago_invalido(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();

        $response = $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'credito',
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'costo' => 9500],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_transferir_mueve_stock_de_bodega_a_vitrina(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 10, 'costo' => 9500]],
        ])->assertOk();

        $response = $this->postJson('/cliente/inventario/transferencias', [
            'producto_id' => $producto->id,
            'cantidad' => 4,
        ]);

        $response->assertOk();
        $this->assertSame(6, InventarioBodega::where('producto_id', $producto->id)->value('stock'));
        $this->assertSame(4, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
    }

    public function test_no_se_puede_transferir_mas_stock_del_que_hay_en_bodega(): void
    {
        $this->crearUsuarioCliente();
        $producto = $this->crearProducto();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal',
            'factura_validada' => false,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 5, 'costo' => 9500]],
        ])->assertOk();

        $response = $this->postJson('/cliente/inventario/transferencias', [
            'producto_id' => $producto->id,
            'cantidad' => 6,
        ]);

        $response->assertStatus(422);
        $this->assertSame(5, InventarioBodega::where('producto_id', $producto->id)->value('stock'));
        $this->assertSame(0, InventarioVitrina::where('producto_id', $producto->id)->value('stock'));
    }
}
