<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\DocumentoSoporte;
use App\Models\Cliente\Gasto;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reporte de gastos a la DIAN (Documento Soporte de adquisiciones) -
 * espejo de FacturacionTest pero del lado de lo que el negocio PAGA, no
 * de lo que vende. Solo Arriendo es reportable por este endpoint: es el
 * caso real de pagarle a alguien que no puede facturarte (ver
 * FacturacionController::storeGasto()). Servicios casi siempre viene de
 * un proveedor formal que ya factura, y Nómina no es un documento
 * soporte -es un sistema DIAN aparte (Nómina Electrónica), con su propio
 * módulo real (ver NominaTest.php); 'nomina' ya ni siquiera es una
 * categoría creable en Gastos (GastoController::store()).
 */
class FacturacionGastosTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id'     => $rol->id,
            'empresa_id' => Empresa::factory()->create(['tiene_facturacion' => true])->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    private function crearGasto(string $categoria, float $monto = 50000): Gasto
    {
        $this->postJson('/cliente/gastos', [
            'categoria'   => $categoria,
            'descripcion' => 'Gasto de prueba '.uniqid(),
            'monto'       => $monto,
            'metodo_pago' => 'digital_externo',
        ])->assertOk();

        return Gasto::orderByDesc('id')->firstOrFail();
    }

    /**
     * 'nomina' ya no es una categoría creable vía el endpoint público
     * (GastoController::store() la rechaza, ver comentario ahí) -pagarle
     * a un empleado ahora tiene su propio módulo real (Nómina). Sigue
     * siendo un valor válido en la columna para gastos históricos, así
     * que estos tests la insertan directo para simular ese caso.
     */
    private function crearGastoNominaHistorico(float $monto = 1200000): Gasto
    {
        return Gasto::create([
            'usuario_id'  => auth()->id(),
            'categoria'   => 'nomina',
            'descripcion' => 'Gasto de nómina histórico '.uniqid(),
            'monto'       => $monto,
            'metodo_pago' => 'digital_externo',
            'fecha'       => now(),
        ]);
    }

    public function test_un_documento_soporte_agrupa_los_gastos_de_arriendo_seleccionados_y_los_marca_reportados(): void
    {
        $this->crearUsuarioCliente();
        $gasto1 = $this->crearGasto('arriendo', 300000);
        $gasto2 = $this->crearGasto('arriendo', 80000);

        $response = $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gasto1->id, $gasto2->id],
            'beneficiario_nombre' => 'Arrendador SAS',
        ]);

        $response->assertOk();

        $doc = DocumentoSoporte::firstOrFail();
        $this->assertSame('documento_soporte', $doc->tipo);
        $this->assertSame('Arrendador SAS', $doc->beneficiario_nombre);
        $this->assertCount(2, $doc->gastos);
        $this->assertSame(380000.0, (float) $doc->valor_total);

        $this->assertSame('reportado', $gasto1->fresh()->estado_documento);
        $this->assertSame('reportado', $gasto2->fresh()->estado_documento);
    }

    public function test_documento_soporte_requiere_nombre_de_beneficiario(): void
    {
        $this->crearUsuarioCliente();
        $gasto = $this->crearGasto('arriendo', 300000);

        $response = $this->postJson('/cliente/facturacion/gastos', [
            'tipo'       => 'documento_soporte',
            'gastos_ids' => [$gasto->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_un_gasto_de_servicios_no_se_puede_reportar_por_documento_soporte(): void
    {
        // Servicios casi siempre viene de un proveedor formal que ya
        // factura -no debe poder colarse por Documento Soporte, ni
        // siquiera pegándole directo al endpoint.
        $this->crearUsuarioCliente();
        $gasto = $this->crearGasto('servicios', 80000);

        $response = $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gasto->id],
            'beneficiario_nombre' => 'EPM',
        ]);

        $response->assertStatus(422);
    }

    public function test_el_tipo_nomina_electronica_ya_no_es_valido_en_este_endpoint(): void
    {
        // Nómina se mueve a su propio módulo -este endpoint solo
        // maneja Documento Soporte de ahora en adelante.
        $this->crearUsuarioCliente();
        $gasto = $this->crearGastoNominaHistorico();

        $response = $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'nomina_electronica',
            'gastos_ids'          => [$gasto->id],
            'beneficiario_nombre' => 'Empleado',
        ]);

        $response->assertStatus(422);
    }

    public function test_no_se_puede_reportar_un_gasto_de_otra_empresa(): void
    {
        // Empresa B tiene un gasto de arriendo sin reportar.
        $this->crearUsuarioCliente();
        $gastoAjeno = $this->crearGasto('arriendo', 300000);

        // Empresa A intenta reportarlo como propio.
        $this->crearUsuarioCliente();
        $response = $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gastoAjeno->id],
            'beneficiario_nombre' => 'Alguien',
        ]);

        $response->assertStatus(422);
    }

    public function test_anular_un_documento_de_gasto_devuelve_los_gastos_a_sin_reportar(): void
    {
        $this->crearUsuarioCliente();
        $gasto = $this->crearGasto('arriendo', 300000);

        $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gasto->id],
            'beneficiario_nombre' => 'Arrendador SAS',
        ])->assertOk();

        $doc = DocumentoSoporte::firstOrFail();

        $this->postJson("/cliente/facturacion/gastos/{$doc->id}/anular")->assertOk();

        $this->assertNotNull($doc->fresh()->anulada_en);
        $this->assertSame('sin_reportar', $gasto->fresh()->estado_documento);
    }

    public function test_el_pdf_del_documento_de_gasto_se_descarga_para_uno_propio(): void
    {
        $this->crearUsuarioCliente();
        $gasto = $this->crearGasto('arriendo', 300000);

        $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gasto->id],
            'beneficiario_nombre' => 'Arrendador SAS',
        ])->assertOk();

        $doc = DocumentoSoporte::firstOrFail();

        $response = $this->get("/cliente/facturacion/gastos/{$doc->id}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_no_se_puede_descargar_el_pdf_de_un_documento_de_gasto_de_otra_empresa(): void
    {
        $this->crearUsuarioCliente();
        $gasto = $this->crearGasto('arriendo', 300000);

        $this->postJson('/cliente/facturacion/gastos', [
            'tipo'                => 'documento_soporte',
            'gastos_ids'          => [$gasto->id],
            'beneficiario_nombre' => 'Arrendador SAS',
        ])->assertOk();

        $doc = DocumentoSoporte::firstOrFail();

        // Otro usuario, otra empresa -el route-model-binding con
        // EmpresaScope debe dar 404, igual que con DocumentoElectronico.
        $this->crearUsuarioCliente();

        $this->get("/cliente/facturacion/gastos/{$doc->id}/pdf")->assertNotFound();
    }

    public function test_solo_los_gastos_de_arriendo_aparecen_como_pendientes_de_reportar(): void
    {
        $this->crearUsuarioCliente();
        $this->crearGasto('otros', 15000);
        $this->crearGasto('servicios', 90000);
        $this->crearGastoNominaHistorico();
        $gastoArriendo = $this->crearGasto('arriendo', 300000);

        $response = $this->get('/cliente/facturacion');

        $response->assertOk();
        $pendientes = $response->viewData('gastosSinReportar');
        $this->assertCount(1, $pendientes);
        $this->assertSame($gastoArriendo->id, $pendientes->first()['id']);
    }
}
