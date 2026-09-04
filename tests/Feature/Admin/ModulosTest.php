<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogo de Módulos -de solo lectura (App\Http\Controllers\Admin\
 * ModuloController). Solo hay dos módulos, complementarios entre sí:
 * "Solo Administración" (sin Factus) y "Administración con Factus". No hay
 * precio por módulo -eso vive en Pagos y suscripciones, por plan.
 */
class ModulosTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_los_dos_modulos_son_complementarios_y_suman_el_total(): void
    {
        $this->crearAdmin();

        Empresa::factory()->create(['tiene_facturacion' => true]);
        Empresa::factory()->create(['tiene_facturacion' => true]);
        Empresa::factory()->create(['tiene_facturacion' => false]);

        $response = $this->get('/admin/modulos');

        $response->assertOk();
        $modulos = $response->viewData('modulos')->keyBy('id');

        $this->assertSame(1, $modulos['administracion']['activas']);
        $this->assertSame(2, $modulos['administracion_factus']['activas']);
        $this->assertSame(3, $modulos['administracion']['total']);
        $this->assertSame(3, $modulos['administracion_factus']['total']);
    }

    public function test_muestra_empresas_reales_no_el_catalogo_mock_viejo(): void
    {
        $this->crearAdmin();
        Empresa::factory()->create(['nombre_negocio' => 'Licorera Real de Prueba']);

        $response = $this->get('/admin/modulos');

        $response->assertOk();
        $response->assertSee('Licorera Real de Prueba');
        $response->assertDontSee('Licores El Roble'); // nombre del mock viejo
        $response->assertDontSee('Pasarela de Pagos'); // concepto del mock viejo
    }

    public function test_un_cliente_no_puede_ver_el_catalogo_de_modulos(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $usuario = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($usuario);

        $this->get('/admin/modulos')->assertForbidden();
    }
}
