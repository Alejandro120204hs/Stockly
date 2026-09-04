<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activación de licencias desde el rol Admin. El pago llega por fuera del
 * sistema (Nequi, transferencia...); el admin solo confirma y activa el
 * plan (App\Http\Controllers\Admin\EmpresaController).
 *
 * Reglas de negocio confirmadas con el dueño del producto:
 *  - Renovar ANTES de vencerse suma desde la fecha de vencimiento actual,
 *    no desde hoy -no se pierden los días ya pagados.
 *  - El estado (activo/por_vencer/vencido) se calcula al vuelo contra la
 *    fecha de hoy, sin ningún job programado -solo "suspendido" es una
 *    bandera manual (Empresa::estadoEfectivo()).
 */
class EmpresaLicenciaTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        return $admin;
    }

    private function crearEmpresaCliente(array $overrides = []): Empresa
    {
        return Empresa::factory()->create(array_merge([
            'nombre_negocio' => 'Empresa de prueba',
        ], $overrides));
    }

    public function test_admin_ve_las_empresas_reales_no_datos_mock(): void
    {
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['nombre_negocio' => 'Licorera Real de Prueba']);

        $response = $this->get('/admin/empresas');

        $response->assertOk();
        $response->assertSee('Licorera Real de Prueba');
        $response->assertDontSee('Licores El Roble'); // nombre del mock viejo
    }

    public function test_activar_plan_mensual_sin_vencimiento_previo_suma_un_mes_desde_hoy(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => null]);

        $response = $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'mensual']);

        $response->assertOk();
        $this->assertSame('2026-10-01', $empresa->fresh()->fecha_vencimiento->toDateString());
        $this->assertSame('activo', $empresa->fresh()->estado_suscripcion);
    }

    public function test_activar_planes_suma_los_meses_correctos(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();

        $casos = [
            'mensual' => '2026-10-01',
            'trimestral' => '2026-12-01',
            'semestral' => '2027-03-01',
            'anual' => '2027-09-01',
        ];

        foreach ($casos as $plan => $esperado) {
            $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => null]);
            $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => $plan])->assertOk();
            $this->assertSame($esperado, $empresa->fresh()->fecha_vencimiento->toDateString(), "Plan {$plan} falló");
        }
    }

    public function test_renovar_antes_de_vencerse_suma_desde_el_vencimiento_actual_no_desde_hoy(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();
        // Todavía le quedan 5 días (vence el 6 de septiembre).
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-09-06']);

        $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'mensual'])->assertOk();

        // Debe sumar 1 mes desde el 6 de septiembre (no desde el 1), para
        // no perder los 5 días que ya tenía pagados.
        $this->assertSame('2026-10-06', $empresa->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_renovar_ya_vencida_suma_desde_hoy(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-09-01']);

        $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'mensual'])->assertOk();

        $this->assertSame('2026-10-10', $empresa->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_activar_crea_un_registro_de_historial_con_los_datos_correctos(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $admin = $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-09-06']);

        $this->postJson("/admin/empresas/{$empresa->id}/activar", [
            'plan' => 'trimestral',
            'monto' => 150000,
            'metodo' => 'Nequi',
        ])->assertOk();

        $pago = PagoSuscripcion::where('empresa_id', $empresa->id)->firstOrFail();
        $this->assertSame('trimestral', $pago->plan);
        $this->assertEquals(150000, $pago->monto);
        $this->assertSame('Nequi', $pago->metodo);
        $this->assertSame('activado', $pago->estado);
        $this->assertSame('2026-09-06', $pago->vencimiento_anterior->toDateString());
        $this->assertSame('2026-12-06', $pago->vencimiento_nuevo->toDateString());
        $this->assertSame($admin->id, $pago->usuario_activador_id);
    }

    public function test_activar_sin_monto_ni_metodo_queda_como_opcional(): void
    {
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => null]);

        $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'mensual'])->assertOk();

        $pago = PagoSuscripcion::where('empresa_id', $empresa->id)->firstOrFail();
        $this->assertNull($pago->monto);
        $this->assertNull($pago->metodo);
    }

    public function test_no_se_puede_activar_con_un_plan_invalido(): void
    {
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente();

        $response = $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'quincenal']);

        $response->assertStatus(422);
    }

    public function test_suspender_marca_suspendido_sin_tocar_la_fecha_de_vencimiento(): void
    {
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-12-31', 'estado_suscripcion' => 'activo']);

        $response = $this->postJson("/admin/empresas/{$empresa->id}/suspender");

        $response->assertOk();
        $fresh = $empresa->fresh();
        $this->assertSame('suspendido', $fresh->estado_suscripcion);
        $this->assertSame('2026-12-31', $fresh->fecha_vencimiento->toDateString());
        $this->assertSame('suspendido', $fresh->estadoEfectivo());
    }

    public function test_estado_efectivo_calcula_activo_por_vencer_y_vencido_segun_la_fecha(): void
    {
        $this->travelTo('2026-09-01 10:00:00');

        $activo = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-09-30']);
        $this->assertSame('activo', $activo->estadoEfectivo());

        $porVencer = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-09-05']);
        $this->assertSame('por_vencer', $porVencer->estadoEfectivo());

        $vencido = $this->crearEmpresaCliente(['fecha_vencimiento' => '2026-08-20']);
        $this->assertSame('vencido', $vencido->estadoEfectivo());

        $sinActivar = $this->crearEmpresaCliente(['fecha_vencimiento' => null]);
        $this->assertSame('vencido', $sinActivar->estadoEfectivo());
    }

    public function test_un_cliente_no_puede_entrar_a_las_rutas_de_admin(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $usuario = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($usuario);

        $empresa = $this->crearEmpresaCliente();

        $this->get('/admin/empresas')->assertForbidden();
        $this->postJson("/admin/empresas/{$empresa->id}/activar", ['plan' => 'mensual'])->assertForbidden();
        $this->postJson("/admin/empresas/{$empresa->id}/suspender")->assertForbidden();
        $this->patchJson("/admin/empresas/{$empresa->id}/modulos", ['tiene_facturacion' => true])->assertForbidden();
    }

    /* -----------------------------------------------------------------
     * Módulos -Facturación electrónica (Factus) es el único interruptor
     * real. Nómina va siempre incluida en Administración para todas las
     * empresas, con o sin Factus (no bloquea nada del lado cliente
     * todavía, eso es una fase aparte).
     * ----------------------------------------------------------------- */

    public function test_una_empresa_nueva_nace_sin_facturacion_prendida(): void
    {
        // ->fresh(): el factory solo inserta los campos que declara, el
        // modelo en memoria no vuelve a leer solo los defaults que puso
        // la base de datos para las columnas que no se mandaron.
        $empresa = $this->crearEmpresaCliente()->fresh();

        $this->assertFalse($empresa->tiene_facturacion);
    }

    public function test_admin_puede_prender_y_apagar_facturacion(): void
    {
        $this->crearAdmin();
        $empresa = $this->crearEmpresaCliente();

        $response = $this->patchJson("/admin/empresas/{$empresa->id}/modulos", [
            'tiene_facturacion' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('empresa.tieneFacturacion', true);
        $this->assertTrue($empresa->fresh()->tiene_facturacion);

        $this->patchJson("/admin/empresas/{$empresa->id}/modulos", [
            'tiene_facturacion' => false,
        ])->assertOk();
        $this->assertFalse($empresa->fresh()->tiene_facturacion);
    }
}
