<?php

namespace Tests\Feature\Cliente;

use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bloqueo por suscripción (App\Http\Middleware\EnsureSuscripcionActiva) +
 * módulo de pago manual (App\Http\Controllers\Cliente\SuscripcionController).
 * No hay pasarela -el cliente transfiere por fuera del sistema y sube un
 * comprobante, que queda 'pago_recibido' hasta que el admin lo aprueba o
 * rechaza (ver Admin\PagoController).
 */
class SuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(array $overridesEmpresa = []): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create($overridesEmpresa)->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    public function test_una_empresa_vencida_es_redirigida_a_suscripcion_desde_cualquier_ruta(): void
    {
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        $this->get('/cliente/dashboard')->assertRedirect(route('cliente.suscripcion'));
        $this->get('/cliente/ventas')->assertRedirect(route('cliente.suscripcion'));
        $this->get('/cliente/perfil')->assertRedirect(route('cliente.suscripcion'));
    }

    public function test_una_empresa_suspendida_tambien_es_redirigida(): void
    {
        $this->crearUsuarioCliente(['estado_suscripcion' => 'suspendido', 'fecha_vencimiento' => now()->addMonth()]);

        $this->get('/cliente/dashboard')->assertRedirect(route('cliente.suscripcion'));
    }

    public function test_una_empresa_activa_o_por_vencer_no_es_redirigida(): void
    {
        $this->crearUsuarioCliente(['fecha_vencimiento' => now()->addMonth()]);
        $this->get('/cliente/dashboard')->assertOk();

        $this->crearUsuarioCliente(['fecha_vencimiento' => now()->addDays(3)]);
        $this->get('/cliente/dashboard')->assertOk();
    }

    public function test_suscripcion_sigue_accesible_aunque_este_bloqueada(): void
    {
        $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        $this->get('/cliente/suscripcion')->assertOk();
    }

    public function test_sin_pago_pendiente_muestra_los_planes(): void
    {
        $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        $this->get('/cliente/suscripcion')
            ->assertOk()
            ->assertSee('Mensual')
            ->assertSee('Trimestral')
            ->assertSee('Semestral')
            ->assertSee('Anual')
            ->assertSee('150.000')
            ->assertDontSee('está siendo validado');
    }

    public function test_con_pago_pendiente_muestra_el_mensaje_de_validacion_en_vez_del_formulario(): void
    {
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        PagoSuscripcion::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'plan' => 'mensual',
            'estado' => 'pago_recibido',
            'fecha_activacion' => null,
            'vencimiento_nuevo' => null,
        ]);

        $this->get('/cliente/suscripcion')
            ->assertOk()
            ->assertSee('está siendo validado')
            ->assertDontSee('Enviar comprobante');
    }

    public function test_reportar_un_pago_crea_el_registro_pendiente_sin_tocar_la_empresa(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay(), 'estado_suscripcion' => 'vencido']);

        $response = $this->post('/cliente/suscripcion', [
            'plan' => 'trimestral',
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
        ]);

        $response->assertRedirect();

        $pago = PagoSuscripcion::where('empresa_id', $usuario->empresa_id)->firstOrFail();
        $this->assertSame('pago_recibido', $pago->estado);
        $this->assertSame('trimestral', $pago->plan);
        $this->assertEquals(390000, $pago->monto);
        $this->assertNotNull($pago->comprobante_path);
        Storage::disk('public')->assertExists($pago->comprobante_path);

        // No se activa solo -sigue vencida hasta que el admin lo apruebe.
        $this->assertSame('vencido', $usuario->empresa->fresh()->estado_suscripcion);
    }

    public function test_el_comprobante_acepta_pdf(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        $response = $this->post('/cliente/suscripcion', [
            'plan' => 'mensual',
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pagos_suscripcion', ['empresa_id' => $usuario->empresa_id, 'estado' => 'pago_recibido']);
    }

    public function test_activa_de_sobra_muestra_solo_el_resumen_sin_formulario(): void
    {
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->addMonth()]);

        PagoSuscripcion::factory()->create([
            'empresa_id' => $usuario->empresa_id, 'plan' => 'anual', 'estado' => 'activado', 'fecha_activacion' => now(),
        ]);

        $this->get('/cliente/suscripcion')
            ->assertOk()
            ->assertSee('Anual')
            ->assertDontSee('Elige tu plan')
            ->assertDontSee('Enviar comprobante');
    }

    public function test_por_vencer_muestra_el_resumen_y_el_formulario_para_renovar(): void
    {
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->addDays(3)]);

        PagoSuscripcion::factory()->create([
            'empresa_id' => $usuario->empresa_id, 'plan' => 'mensual', 'estado' => 'activado', 'fecha_activacion' => now(),
        ]);

        $this->get('/cliente/suscripcion')
            ->assertOk()
            ->assertSee('Elige tu plan')
            ->assertSee('Enviar comprobante');
    }

    public function test_no_se_puede_reportar_un_segundo_pago_mientras_el_primero_sigue_pendiente(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuarioCliente(['fecha_vencimiento' => now()->subDay()]);

        PagoSuscripcion::factory()->create([
            'empresa_id' => $usuario->empresa_id, 'estado' => 'pago_recibido',
            'fecha_activacion' => null, 'vencimiento_nuevo' => null,
        ]);

        $this->post('/cliente/suscripcion', [
            'plan' => 'anual',
            'comprobante' => UploadedFile::fake()->image('comprobante2.jpg'),
        ]);

        $this->assertSame(1, PagoSuscripcion::where('empresa_id', $usuario->empresa_id)->count());
    }

    public function test_un_admin_sin_empresa_no_se_ve_afectado_por_el_bloqueo(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rol->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        $this->get('/admin/dashboard')->assertOk();
    }
}
