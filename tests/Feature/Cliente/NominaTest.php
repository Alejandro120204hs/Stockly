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
 * Nómina Electrónica -deliberadamente SIN cálculo legal (nada de salud,
 * pensión, SMMLV ni retención, ver App\Http\Controllers\Cliente\
 * NominaController). El dueño del negocio decide cuánto le paga a cada
 * empleado; estos tests cubren el registro de empleados y la emisión de
 * documentos que solo dejan constancia de ese pago.
 */
class NominaTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id'     => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    private function datosEmpleado(array $overrides = []): array
    {
        return array_merge([
            'nombres'          => 'Juan Carlos',
            'apellidos'        => 'Pérez Gómez',
            'tipo_documento'   => 'CC',
            'numero_documento' => '1020304050',
            'cargo'            => 'Vendedor',
            'salario'          => 1300000,
        ], $overrides);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado($overrides))->assertOk();

        return Empleado::where('numero_documento', $overrides['numero_documento'] ?? '1020304050')->firstOrFail();
    }

    public function test_crear_empleado_con_datos_completos(): void
    {
        $this->crearUsuarioCliente();

        $response = $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado());

        $response->assertOk();
        $this->assertDatabaseHas('empleados', [
            'nombres'          => 'Juan Carlos',
            'apellidos'        => 'Pérez Gómez',
            'numero_documento' => '1020304050',
        ]);
    }

    public function test_no_se_puede_repetir_el_numero_de_documento_entre_empleados_de_la_misma_empresa(): void
    {
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado())->assertOk();

        $response = $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado(['nombres' => 'Otro']));

        $response->assertStatus(422);
        $this->assertSame(1, Empleado::count());
    }

    public function test_dos_empresas_distintas_pueden_tener_un_empleado_con_el_mismo_numero_de_documento(): void
    {
        // La misma persona podría trabajar en dos negocios distintos que
        // ambos usan Stockly -no debe bloquearse entre empresas.
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado())->assertOk();

        $this->crearUsuarioCliente();
        $response = $this->postJson('/cliente/nomina/empleados', $this->datosEmpleado());

        $response->assertOk();
        $this->assertSame(2, Empleado::withoutGlobalScopes()->count());
    }

    public function test_editar_empleado_actualiza_sus_datos(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();

        $response = $this->putJson("/cliente/nomina/empleados/{$empleado->id}", $this->datosEmpleado([
            'cargo' => 'Supervisor',
        ]));

        $response->assertOk();
        $this->assertSame('Supervisor', $empleado->fresh()->cargo);
    }

    public function test_eliminar_empleado_lo_quita_de_la_lista_pero_conserva_sus_documentos(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();

        $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleado->id, 'monto_pagado' => 1300000]],
        ])->assertOk();

        $this->deleteJson("/cliente/nomina/empleados/{$empleado->id}")->assertOk();

        $this->assertSoftDeleted('empleados', ['id' => $empleado->id]);
        $this->assertDatabaseHas('nomina_documentos', ['empleado_id' => $empleado->id]);
    }

    public function test_pagar_nomina_crea_un_documento_por_empleado_con_monto_mayor_a_cero(): void
    {
        $this->crearUsuarioCliente();
        $empleado1 = $this->crearEmpleado(['numero_documento' => '1111111111']);
        $empleado2 = $this->crearEmpleado(['numero_documento' => '2222222222']);

        $response = $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [
                ['empleado_id' => $empleado1->id, 'monto_pagado' => 1500000],
                // Monto en 0 -no debe generar documento para este.
                ['empleado_id' => $empleado2->id, 'monto_pagado' => 0],
            ],
        ]);

        $response->assertOk();
        $this->assertSame(1, NominaDocumento::count());
        $this->assertDatabaseHas('nomina_documentos', [
            'empleado_id'  => $empleado1->id,
            'monto_pagado' => 1500000,
            'periodo'      => 'Enero 2026',
        ]);
        $this->assertDatabaseMissing('nomina_documentos', ['empleado_id' => $empleado2->id]);
    }

    public function test_pagar_nomina_paga_montos_distintos_a_cada_empleado_sin_calcular_nada(): void
    {
        // El dueño decide cuánto paga -uno por debajo del salario mínimo y
        // otro por encima, ambos deben quedar exactamente como se escribió.
        $this->crearUsuarioCliente();
        $empleado1 = $this->crearEmpleado(['numero_documento' => '1111111111']);
        $empleado2 = $this->crearEmpleado(['numero_documento' => '2222222222']);

        $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [
                ['empleado_id' => $empleado1->id, 'monto_pagado' => 400000],
                ['empleado_id' => $empleado2->id, 'monto_pagado' => 3000000],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('nomina_documentos', ['empleado_id' => $empleado1->id, 'monto_pagado' => 400000]);
        $this->assertDatabaseHas('nomina_documentos', ['empleado_id' => $empleado2->id, 'monto_pagado' => 3000000]);
    }

    public function test_no_se_puede_pagar_nomina_sin_ningun_monto_mayor_a_cero(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();

        $response = $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleado->id, 'monto_pagado' => 0]],
        ]);

        $response->assertStatus(422);
    }

    public function test_no_se_puede_pagar_nomina_a_un_empleado_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $empleadoAjeno = $this->crearEmpleado();

        $this->crearUsuarioCliente();
        $response = $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleadoAjeno->id, 'monto_pagado' => 500000]],
        ]);

        $response->assertStatus(422);
    }

    public function test_anular_un_documento_de_nomina_lo_marca_anulado(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();
        $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleado->id, 'monto_pagado' => 1300000]],
        ])->assertOk();
        $documento = NominaDocumento::firstOrFail();

        $this->postJson("/cliente/nomina/documentos/{$documento->id}/anular")->assertOk();

        $this->assertNotNull($documento->fresh()->anulada_en);
    }

    public function test_el_pdf_del_documento_de_nomina_se_descarga_para_uno_propio(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();
        $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleado->id, 'monto_pagado' => 1300000]],
        ])->assertOk();
        $documento = NominaDocumento::firstOrFail();

        $response = $this->get("/cliente/nomina/documentos/{$documento->id}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_no_se_puede_descargar_el_pdf_de_un_documento_de_nomina_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $empleado = $this->crearEmpleado();
        $this->postJson('/cliente/nomina/documentos', [
            'periodo'    => 'Enero 2026',
            'fecha_pago' => now()->toDateString(),
            'pagos'      => [['empleado_id' => $empleado->id, 'monto_pagado' => 1300000]],
        ])->assertOk();
        $documento = NominaDocumento::firstOrFail();

        $this->crearUsuarioCliente();
        $this->get("/cliente/nomina/documentos/{$documento->id}/pdf")->assertNotFound();
    }

    public function test_una_empresa_no_ve_empleados_de_otra_en_el_index(): void
    {
        $this->crearUsuarioCliente();
        $this->crearEmpleado();

        $this->crearUsuarioCliente();
        $html = $this->get('/cliente/nomina')->assertOk()->getContent();

        preg_match('/id="empleadosData"[^>]*>(.*?)<\/script>/s', $html, $m);
        $empleados = json_decode($m[1], true);

        $this->assertCount(0, $empleados, 'Empresa A no debe ver empleados de empresa B');
    }
}
