<?php

namespace Tests\Feature\Cliente;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Facturación electrónica es el ÚNICO módulo que depende de
 * Empresa::tiene_facturacion -el resto de "Administración" (Ventas, Caja,
 * Inventario, Gastos, Nómina, Reportes...) lo tienen todas las empresas.
 * Ver App\Http\Middleware\EnsureFacturacionActiva.
 */
class FacturacionModuloTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(bool $tieneFacturacion): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create(['tiene_facturacion' => $tieneFacturacion])->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    public function test_una_empresa_sin_el_modulo_no_puede_entrar_a_facturacion(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/facturacion')
            ->assertRedirect(route('cliente.dashboard'));
    }

    public function test_una_empresa_con_el_modulo_si_puede_entrar_a_facturacion(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: true);

        $this->get('/cliente/facturacion')->assertOk();
    }

    public function test_el_dashboard_avisa_por_que_la_redirigio(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/facturacion')->assertRedirect(route('cliente.dashboard'));

        // El aviso es un flash de sesión de un solo uso -vive en la
        // siguiente petición, justo la que sigue al redirect.
        $this->get('/cliente/dashboard')->assertSee('no incluye facturación electrónica');
    }

    public function test_el_sidebar_no_muestra_facturacion_sin_el_modulo(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/dashboard')
            ->assertOk()
            ->assertDontSee('cliente/facturacion', false);
    }

    public function test_el_sidebar_si_muestra_facturacion_con_el_modulo(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: true);

        $this->get('/cliente/dashboard')
            ->assertOk()
            ->assertSee('cliente/facturacion', false);
    }

    /**
     * "Administración" (todo lo demás) no depende de tiene_facturacion -es
     * la regla central de este módulo: solo Facturación se bloquea.
     */
    public function test_el_resto_de_administracion_funciona_sin_el_modulo_de_facturacion(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/dashboard')->assertOk();
        $this->get('/cliente/ventas')->assertOk();
        $this->get('/cliente/inventario')->assertOk();
        $this->get('/cliente/proveedores')->assertOk();
        $this->get('/cliente/caja')->assertOk();
        $this->get('/cliente/gastos')->assertOk();
        $this->get('/cliente/nomina')->assertOk();
        $this->get('/cliente/reportes')->assertOk();
    }
}
