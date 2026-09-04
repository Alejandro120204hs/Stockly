<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard del Super Admin -datos reales (App\Http\Controllers\Admin\
 * DashboardController). "Pagos pendientes de activar" del mock original
 * no aplica (no hay pasarela que confirme aparte del admin), así que se
 * reemplazó por "Actividad reciente" con el historial real de
 * activaciones (PagoSuscripcion).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_el_dashboard_cuenta_las_empresas_por_estado_efectivo(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();

        Empresa::factory()->create(['fecha_vencimiento' => '2026-09-30']); // activo
        Empresa::factory()->create(['fecha_vencimiento' => '2026-09-05']); // por_vencer
        Empresa::factory()->create(['fecha_vencimiento' => '2026-08-01']); // vencido
        Empresa::factory()->create(['fecha_vencimiento' => '2026-12-31', 'estado_suscripcion' => 'suspendido']); // suspendido

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('empresasActivas', 1);
        $response->assertViewHas('porVencer', 1);
        $response->assertViewHas('vencidas', 1);
        $response->assertViewHas('suspendidas', 1);
    }

    public function test_proximas_a_vencer_solo_incluye_por_vencer_ordenadas_por_fecha(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();

        Empresa::factory()->create(['nombre_negocio' => 'Vence en 6 días', 'fecha_vencimiento' => '2026-09-07']);
        Empresa::factory()->create(['nombre_negocio' => 'Vence en 2 días', 'fecha_vencimiento' => '2026-09-03']);
        Empresa::factory()->create(['nombre_negocio' => 'Vencida ya', 'fecha_vencimiento' => '2026-08-01']);
        Empresa::factory()->create(['nombre_negocio' => 'Muy activa', 'fecha_vencimiento' => '2026-12-31']);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $proximas = $response->viewData('proximasAVencer');
        $this->assertCount(2, $proximas);
        $this->assertSame('Vence en 2 días', $proximas->first()['nombre']);
        $this->assertSame(2, $proximas->first()['dias']);
    }

    public function test_actividad_reciente_refleja_las_ultimas_activaciones_reales(): void
    {
        $admin = $this->crearAdmin();
        $empresa = Empresa::factory()->create(['nombre_negocio' => 'Licorera Reciente']);

        $this->postJson("/admin/empresas/{$empresa->id}/activar", [
            'plan' => 'trimestral',
            'monto' => 150000,
        ])->assertOk();

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $activaciones = $response->viewData('activacionesRecientes');
        $this->assertCount(1, $activaciones);
        $this->assertSame('Licorera Reciente', $activaciones->first()['empresa']);
        $this->assertSame('Trimestral', $activaciones->first()['plan']);
        $this->assertEquals(150000, $activaciones->first()['monto']);
    }

    public function test_ingresos_del_mes_suma_solo_las_activaciones_del_mes_actual(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $admin = $this->crearAdmin();
        $empresa = Empresa::factory()->create();

        PagoSuscripcion::create([
            'empresa_id' => $empresa->id, 'plan' => 'mensual', 'monto' => 90000, 'estado' => 'activado',
            'fecha_pago' => now(), 'fecha_activacion' => now(),
            'vencimiento_anterior' => null, 'vencimiento_nuevo' => now()->addMonth(),
            'usuario_activador_id' => $admin->id,
        ]);
        // Del mes pasado -no debe contar en "ingresos del mes".
        PagoSuscripcion::create([
            'empresa_id' => $empresa->id, 'plan' => 'mensual', 'monto' => 70000, 'estado' => 'activado',
            'fecha_pago' => now()->subMonth(), 'fecha_activacion' => now()->subMonth(),
            'vencimiento_anterior' => null, 'vencimiento_nuevo' => now(),
            'usuario_activador_id' => $admin->id,
        ]);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('ingresosMes', 90000.0);
    }

    public function test_empresas_por_modulo_reparte_facturacion_y_administracion_entre_todas(): void
    {
        $this->crearAdmin();

        // Facturación electrónica (con Factus) vs Administración (sin Factus,
        // solo inventario/caja) -son complementarios, deben sumar el total.
        Empresa::factory()->create(['tiene_facturacion' => true]);
        Empresa::factory()->create(['tiene_facturacion' => true]);
        Empresa::factory()->create(['tiene_facturacion' => false]);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('countFacturacion', 2);
        $response->assertViewHas('countAdministracion', 1);
        $response->assertViewHas('totalEmpresas', 3);
    }

    public function test_un_cliente_no_puede_ver_el_dashboard_de_admin(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $usuario = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($usuario);

        $this->get('/admin/dashboard')->assertForbidden();
    }

    /**
     * El badge de "pagos pendientes" en el sidebar se calcula en
     * App\View\Components\AdminLayout::render() -por eso aparece en
     * CUALQUIER página del panel admin, no solo en /admin/pagos.
     */
    public function test_el_badge_de_pagos_pendientes_aparece_en_cualquier_pagina_admin(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();
        PagoSuscripcion::factory()->count(2)->create([
            'empresa_id' => $empresa->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null,
        ]);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('admin-nav-item__badge', false);
        $response->assertSee('>2<', false);
    }

    public function test_sin_pagos_pendientes_no_aparece_el_badge(): void
    {
        $this->crearAdmin();

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertDontSee('admin-nav-item__badge', false);
    }
}
