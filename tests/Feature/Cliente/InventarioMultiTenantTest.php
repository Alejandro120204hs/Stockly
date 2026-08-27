<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\CategoriaProducto;
use App\Models\Cliente\Producto;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regla de negocio explícita del cliente: "a una empresa no le puede
 * aparecer el inventario ni las ventas ni nada de otra". Estos tests
 * protegen el EmpresaScope (Global Scope) contra una regresión futura.
 */
class InventarioMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        return User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);
    }

    public function test_una_empresa_no_ve_productos_de_otra_empresa_en_el_index(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        $categoriaB = CategoriaProducto::create(['nombre' => 'Bebidas']);
        Producto::create([
            'categoria_id' => $categoriaB->id,
            'nombre' => 'Producto de empresa B',
            'precio_costo' => 1000,
            'precio_venta' => 2000,
            'unidad_medida' => 'Unidad',
        ]);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        $this->get('/cliente/inventario')
            ->assertOk()
            ->assertDontSee('Producto de empresa B');

        $this->assertSame(0, Producto::count());
        $this->assertSame(0, CategoriaProducto::count());
    }

    public function test_no_se_puede_leer_un_producto_de_otra_empresa_por_id(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        $categoriaB = CategoriaProducto::create(['nombre' => 'Bebidas']);
        $productoB = Producto::create([
            'categoria_id' => $categoriaB->id,
            'nombre' => 'Producto de empresa B',
            'precio_costo' => 1000,
            'precio_venta' => 2000,
            'unidad_medida' => 'Unidad',
        ]);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        $this->assertNull(Producto::find($productoB->id));
    }

    public function test_no_se_puede_editar_un_producto_de_otra_empresa_pasando_su_id_directamente(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        $categoriaB = CategoriaProducto::create(['nombre' => 'Bebidas']);
        $productoB = Producto::create([
            'categoria_id' => $categoriaB->id,
            'nombre' => 'Producto de empresa B',
            'precio_costo' => 1000,
            'precio_venta' => 2000,
            'unidad_medida' => 'Unidad',
        ]);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        $response = $this->putJson("/cliente/inventario/productos/{$productoB->id}", [
            'nombre' => 'Hackeado',
            'categoria' => 'Bebidas',
            'precio_costo' => 1,
            'precio_venta' => 1,
            'unidad_medida' => 'Unidad',
        ]);

        // El route-model-binding de Laravel resuelve el producto pasando
        // por el Global Scope -si el scope filtra bien, el producto de la
        // empresa B "no existe" para la empresa A y esto debe dar 404, no
        // un 403 ni (peor) un 200 con el producto editado.
        $response->assertNotFound();
        $this->assertSame('Producto de empresa B', $productoB->fresh()->nombre);
    }

    public function test_no_se_puede_eliminar_un_producto_de_otra_empresa_pasando_su_id_directamente(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        $categoriaB = CategoriaProducto::create(['nombre' => 'Bebidas']);
        $productoB = Producto::create([
            'categoria_id' => $categoriaB->id,
            'nombre' => 'Producto de empresa B',
            'precio_costo' => 1000,
            'precio_venta' => 2000,
            'unidad_medida' => 'Unidad',
        ]);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        $this->deleteJson("/cliente/inventario/productos/{$productoB->id}")
            ->assertNotFound();

        $this->assertNotSoftDeleted($productoB);
    }

    public function test_no_se_puede_renombrar_una_categoria_de_otra_empresa_aunque_se_sepa_el_nombre(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        CategoriaProducto::create(['nombre' => 'Bebidas']);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        $response = $this->putJson('/cliente/inventario/categorias', [
            'nombre_actual' => 'Bebidas',
            'nombre_nuevo' => 'Hackeado',
        ]);

        $response->assertNotFound();
        $this->actingAs($usuarioB);
        $this->assertSame('Bebidas', CategoriaProducto::first()->nombre);
    }

    /**
     * Regresión del hallazgo de seguridad: un admin (empresa_id null, no
     * pertenece a ningún negocio cliente) no debe poder entrar a las rutas
     * de /cliente/inventario -antes de la corrección, el middleware 'rol'
     * no existía y el EmpresaScope se saltaba el filtro por completo para
     * cuentas sin empresa_id, exponiendo el inventario de TODAS las
     * empresas a cualquier admin.
     */
    public function test_un_admin_sin_empresa_no_puede_entrar_a_inventario(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rolAdmin->id, 'empresa_id' => null]);

        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        $categoriaB = CategoriaProducto::create(['nombre' => 'Bebidas']);
        Producto::create([
            'categoria_id' => $categoriaB->id,
            'nombre' => 'Producto de empresa B',
            'precio_costo' => 1000,
            'precio_venta' => 2000,
            'unidad_medida' => 'Unidad',
        ]);

        $this->actingAs($admin);

        $this->get('/cliente/inventario')->assertForbidden();
    }

    public function test_una_categoria_nueva_de_una_empresa_no_aparece_como_duplicada_para_otra(): void
    {
        $usuarioB = $this->crearUsuarioCliente();
        $this->actingAs($usuarioB);
        CategoriaProducto::create(['nombre' => 'Bebidas']);

        $usuarioA = $this->crearUsuarioCliente();
        $this->actingAs($usuarioA);

        // Aunque la empresa B ya tenga "Bebidas", la empresa A debe poder
        // crear su propia categoría con el mismo nombre sin choque.
        $this->postJson('/cliente/inventario/categorias', ['nombre' => 'Bebidas'])
            ->assertOk();

        $this->assertSame(1, CategoriaProducto::count());
    }
}
