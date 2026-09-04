<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historial de Pagos y suscripciones (App\Http\Controllers\Admin\
 * PagoController). La activación manual sigue siendo cosa del panel de
 * Empresas (nace ya 'activado'), pero ahora también existen pagos
 * 'pago_recibido' -reportados por el cliente con comprobante desde
 * /cliente/suscripcion- que este controlador aprueba o rechaza.
 */
class PagosTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_el_historial_muestra_los_pagos_reales_no_datos_mock(): void
    {
        $admin = $this->crearAdmin();
        $empresa = Empresa::factory()->create(['nombre_negocio' => 'Licorera Real de Prueba']);

        PagoSuscripcion::create([
            'empresa_id' => $empresa->id, 'plan' => 'trimestral', 'monto' => 150000, 'metodo' => 'Nequi',
            'estado' => 'activado', 'fecha_pago' => now(), 'fecha_activacion' => now(),
            'vencimiento_anterior' => null, 'vencimiento_nuevo' => now()->addMonths(3),
            'usuario_activador_id' => $admin->id,
        ]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertSee('Licorera Real de Prueba');
        $response->assertDontSee('Licores El Roble'); // nombre del mock viejo
        $response->assertDontSee('Pasarela de Pagos'); // "módulo" del mock viejo
    }

    public function test_el_historial_incluye_todas_las_activaciones_no_solo_las_ultimas_5(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();

        PagoSuscripcion::factory()->count(8)->create([
            'empresa_id' => $empresa->id,
            'plan' => 'mensual',
        ]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertViewHas('totalActivaciones', 8);
    }

    public function test_ingresos_del_mes_solo_suma_las_activaciones_del_mes_actual(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();

        PagoSuscripcion::factory()->create([
            'empresa_id' => $empresa->id, 'monto' => 70000, 'fecha_activacion' => now(),
        ]);
        PagoSuscripcion::factory()->create([
            'empresa_id' => $empresa->id, 'monto' => 90000, 'fecha_activacion' => now()->subMonth(),
        ]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertViewHas('ingresosMes', 70000.0);
        $response->assertViewHas('ingresosTotal', 160000.0);
    }

    public function test_un_cliente_no_puede_ver_el_historial_de_pagos(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $usuario = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($usuario);

        $this->get('/admin/pagos')->assertForbidden();
    }

    public function test_los_pagos_pendientes_no_cuentan_en_los_ingresos(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();

        PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'monto' => 70000, 'estado' => 'activado', 'fecha_activacion' => now()]);
        PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'monto' => 150000, 'estado' => 'pago_recibido', 'fecha_activacion' => null]);
        PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'monto' => 150000, 'estado' => 'rechazado', 'fecha_activacion' => null]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertViewHas('ingresosMes', 70000.0);
        $response->assertViewHas('ingresosTotal', 70000.0);
        $response->assertViewHas('totalActivaciones', 1);
        $response->assertViewHas('pendientes', 1);
    }

    /**
     * Mismas reglas ya probadas en EmpresaLicenciaTest para
     * EmpresaController::activar() -acá se disparan igual, pero
     * aprobando un pago que el cliente ya reportó en vez de crear uno
     * nuevo (ver Empresa::calcularNuevoVencimiento()).
     */
    public function test_aprobar_un_pago_pendiente_activa_la_empresa_y_extiende_desde_el_vencimiento_futuro(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $admin = $this->crearAdmin();
        $empresa = Empresa::factory()->create(['fecha_vencimiento' => '2026-09-06', 'estado_suscripcion' => 'vencido']);

        $pago = PagoSuscripcion::factory()->create([
            'empresa_id' => $empresa->id, 'plan' => 'trimestral', 'monto' => 390000,
            'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null,
        ]);

        $response = $this->postJson("/admin/pagos/{$pago->id}/aprobar");

        $response->assertOk();
        $response->assertJsonPath('pago.estado', 'activado');

        $empresaFresca = $empresa->fresh();
        $this->assertSame('activo', $empresaFresca->estado_suscripcion);
        $this->assertSame('2026-12-06', $empresaFresca->fecha_vencimiento->toDateString());

        $pagoFresco = $pago->fresh();
        $this->assertSame('activado', $pagoFresco->estado);
        $this->assertSame('2026-09-06', $pagoFresco->vencimiento_anterior->toDateString());
        $this->assertSame('2026-12-06', $pagoFresco->vencimiento_nuevo->toDateString());
        $this->assertSame($admin->id, $pagoFresco->usuario_activador_id);
    }

    public function test_aprobar_una_empresa_sin_vencimiento_previo_suma_desde_hoy(): void
    {
        $this->travelTo('2026-09-01 10:00:00');
        $this->crearAdmin();
        $empresa = Empresa::factory()->create(['fecha_vencimiento' => null, 'estado_suscripcion' => 'vencido']);

        $pago = PagoSuscripcion::factory()->create([
            'empresa_id' => $empresa->id, 'plan' => 'mensual',
            'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null,
        ]);

        $this->postJson("/admin/pagos/{$pago->id}/aprobar")->assertOk();

        $this->assertSame('2026-10-01', $empresa->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_no_se_puede_aprobar_un_pago_que_ya_fue_revisado(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();
        $pago = PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'activado']);

        $this->postJson("/admin/pagos/{$pago->id}/aprobar")->assertStatus(422);
    }

    public function test_rechazar_guarda_el_motivo_sin_tocar_la_empresa(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create(['fecha_vencimiento' => '2026-12-31', 'estado_suscripcion' => 'activo']);
        $pago = PagoSuscripcion::factory()->create([
            'empresa_id' => $empresa->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null,
        ]);

        $response = $this->postJson("/admin/pagos/{$pago->id}/rechazar", [
            'motivo' => 'El comprobante no coincide con el monto del plan.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('pago.estado', 'rechazado');

        $pagoFresco = $pago->fresh();
        $this->assertSame('rechazado', $pagoFresco->estado);
        $this->assertSame('El comprobante no coincide con el monto del plan.', $pagoFresco->motivo_rechazo);

        $empresaFresca = $empresa->fresh();
        $this->assertSame('activo', $empresaFresca->estado_suscripcion);
        $this->assertSame('2026-12-31', $empresaFresca->fecha_vencimiento->toDateString());
    }

    public function test_rechazar_sin_motivo_falla_la_validacion(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create();
        $pago = PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null]);

        $this->postJson("/admin/pagos/{$pago->id}/rechazar", [])->assertStatus(422);
    }

    public function test_el_detalle_incluye_correo_y_telefono_de_la_empresa(): void
    {
        $this->crearAdmin();
        $empresa = Empresa::factory()->create([
            'correo_contacto' => 'contacto@licorera.test',
            'telefono_contacto' => '3001112233',
        ]);
        PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertSee('contacto@licorera.test');
        $response->assertSee('3001112233');
    }

    public function test_si_la_empresa_no_tiene_telefono_propio_usa_el_del_usuario_como_respaldo(): void
    {
        $this->crearAdmin();
        // Sin telefono_contacto -pasaba con toda empresa registrada antes
        // de que el registro público empezara a copiarlo (ver
        // RegistrationTest::test_new_users_can_register).
        $empresa = Empresa::factory()->create(['telefono_contacto' => null]);
        $rolCliente = Rol::firstOrCreate(['nombre' => 'cliente']);
        User::factory()->create(['rol_id' => $rolCliente->id, 'empresa_id' => $empresa->id, 'telefono' => '3009998877']);
        PagoSuscripcion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null]);

        $response = $this->get('/admin/pagos');

        $response->assertOk();
        $response->assertSee('3009998877');
    }

    public function test_un_cliente_no_puede_aprobar_ni_rechazar_pagos(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $empresaCliente = Empresa::factory()->create();
        $usuario = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => $empresaCliente->id]);
        $this->actingAs($usuario);

        $pago = PagoSuscripcion::factory()->create(['empresa_id' => $empresaCliente->id, 'estado' => 'pago_recibido', 'fecha_activacion' => null, 'vencimiento_nuevo' => null]);

        $this->postJson("/admin/pagos/{$pago->id}/aprobar")->assertForbidden();
        $this->postJson("/admin/pagos/{$pago->id}/rechazar", ['motivo' => 'x'])->assertForbidden();
    }
}
