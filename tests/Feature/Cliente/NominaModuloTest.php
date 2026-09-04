<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\Empleado;
use App\Models\Cliente\NominaDocumento;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A diferencia de Facturación, Nómina NUNCA se bloquea -toda empresa puede
 * pagarle a sus empleados (ver [[stockly-domain-model]]: "todas tienen
 * administración"). Lo que cambia sin Factus es solo la piel: la pestaña
 * deja de llamarse "Nómina electrónica" y pasa a ser "Pagar nómina", sin
 * ningún rastro de CUNE/DIAN (ni en la vista ni en el PDF) -es un
 * comprobante de pago normal, no un documento electrónico que esta
 * empresa no puede emitir de verdad.
 */
class NominaModuloTest extends TestCase
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

    public function test_sin_facturacion_la_pestana_dice_pagar_nomina_sin_mencionar_dian(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/nomina')
            ->assertOk()
            ->assertSee('Pagar nómina')
            ->assertDontSee('Nómina electrónica')
            ->assertDontSee('Verificación DIAN')
            ->assertDontSee('Emitir a la DIAN');
    }

    public function test_con_facturacion_la_pestana_sigue_diciendo_nomina_electronica(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: true);

        $this->get('/cliente/nomina')
            ->assertOk()
            ->assertSee('Nómina electrónica')
            ->assertSee('Verificación DIAN')
            ->assertSee('Emitir a la DIAN');
    }

    public function test_el_pdf_de_nomina_sin_facturacion_no_menciona_la_dian(): void
    {
        $usuario = $this->crearUsuarioCliente(tieneFacturacion: false);
        $empleado = Empleado::create([
            'empresa_id' => $usuario->empresa_id,
            'nombres' => 'Juan', 'apellidos' => 'Pérez',
            'tipo_documento' => 'CC', 'numero_documento' => '123',
        ]);
        $documento = NominaDocumento::create([
            'empresa_id' => $usuario->empresa_id,
            'empleado_id' => $empleado->id,
            'numero' => 'NE-2026-001',
            'cune' => 'cune-simulado',
            'periodo' => 'Enero 2026',
            'monto_pagado' => 500000,
            'fecha_pago' => now(),
            'fecha_emision' => now(),
        ]);

        $response = $this->get("/cliente/nomina/documentos/{$documento->id}/pdf");

        $response->assertOk();
        $this->assertStringNotContainsString('DIAN', $response->getContent());
        $this->assertStringNotContainsString('CUNE', $response->getContent());
    }

    /**
     * El checkbox de Ventas sirve para dos cosas -con Factus prepara el
     * dato para facturar de verdad; sin Factus solo pone el nombre en el
     * recibo normal (que ya existe para cualquier empresa). El texto debe
     * dejar claro cuál de las dos promete.
     */
    public function test_el_checkbox_de_ventas_cambia_de_texto_sin_facturacion(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: false);

        $this->get('/cliente/ventas')
            ->assertOk()
            ->assertSee('¿Poner el nombre del cliente en el recibo?')
            ->assertDontSee('¿El cliente necesita factura a su nombre?');
    }

    public function test_el_checkbox_de_ventas_mantiene_el_texto_con_facturacion(): void
    {
        $this->crearUsuarioCliente(tieneFacturacion: true);

        $this->get('/cliente/ventas')
            ->assertOk()
            ->assertSee('¿El cliente necesita factura a su nombre?');
    }
}
