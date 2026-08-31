<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente\DocumentoElectronico;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Venta;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento multi-tenant del módulo de Facturación.
 * Una empresa no puede ver, emitir ni anular documentos de otra.
 */
class FacturacionMultiTenantTest extends TestCase
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

    private function crearVentaConCaja(): Venta
    {
        $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        $this->postJson('/cliente/inventario/productos', [
            'nombre'        => 'Producto Test',
            'categoria'     => 'General',
            'precio_costo'  => 10000,
            'precio_venta'  => 20000,
            'unidad_medida' => 'Unidad',
        ])->assertOk();
        $prod = Producto::where('nombre', 'Producto Test')->firstOrFail();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1, 'costo' => 10000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $prod->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 20000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();

        return Venta::noAnuladas()->where('estado_facturacion', 'sin_facturar')->firstOrFail();
    }

    public function test_una_empresa_no_ve_documentos_de_otra_en_el_index(): void
    {
        // Empresa B emite un documento.
        $this->crearUsuarioCliente();
        $venta = $this->crearVentaConCaja();
        $this->postJson('/cliente/facturacion', [
            'tipo'                       => 'factura_individual',
            'ventas_ids'                 => [$venta->id],
            'comprador_tipo_documento'   => 'CC',
            'comprador_numero_documento' => '12345678',
            'comprador_nombre'           => 'Cliente B',
        ])->assertOk();

        // Empresa A entra al módulo de facturación.
        $this->crearUsuarioCliente();
        $html = $this->get('/cliente/facturacion')->assertOk()->getContent();

        preg_match('/id="facturacionData"[^>]*>(.*?)<\/script>/s', $html, $m);
        $docs = json_decode($m[1], true);

        $this->assertCount(0, $docs, 'Empresa A no debe ver documentos de empresa B');
    }

    public function test_no_se_puede_anular_un_documento_de_otra_empresa(): void
    {
        // Empresa B crea y emite un documento.
        $this->crearUsuarioCliente();
        $venta = $this->crearVentaConCaja();
        $this->postJson('/cliente/facturacion', [
            'tipo'                       => 'factura_individual',
            'ventas_ids'                 => [$venta->id],
            'comprador_tipo_documento'   => 'CC',
            'comprador_numero_documento' => '12345678',
            'comprador_nombre'           => 'Cliente B',
        ])->assertOk();
        $docB = DocumentoElectronico::withoutGlobalScopes()->latest()->first();

        // Empresa A intenta anular ese documento pasando su id directamente.
        $this->crearUsuarioCliente();
        $this->postJson("/cliente/facturacion/{$docB->id}/anular")
            ->assertNotFound();

        // El documento de empresa B sigue activo.
        $this->assertNull($docB->fresh()->anulada_en);
    }

    public function test_el_pdf_de_la_factura_se_descarga_para_un_documento_propio(): void
    {
        $this->crearUsuarioCliente();
        $venta = $this->crearVentaConCaja();
        $this->postJson('/cliente/facturacion', [
            'tipo'                       => 'factura_individual',
            'ventas_ids'                 => [$venta->id],
            'comprador_tipo_documento'   => 'CC',
            'comprador_numero_documento' => '12345678',
            'comprador_nombre'           => 'Cliente A',
        ])->assertOk();
        $doc = DocumentoElectronico::firstOrFail();

        $response = $this->get("/cliente/facturacion/{$doc->id}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_no_se_puede_descargar_el_pdf_de_un_documento_de_otra_empresa(): void
    {
        // Empresa B emite un documento.
        $this->crearUsuarioCliente();
        $ventaB = $this->crearVentaConCaja();
        $this->postJson('/cliente/facturacion', [
            'tipo'                       => 'factura_individual',
            'ventas_ids'                 => [$ventaB->id],
            'comprador_tipo_documento'   => 'CC',
            'comprador_numero_documento' => '12345678',
            'comprador_nombre'           => 'Cliente B',
        ])->assertOk();
        $docB = DocumentoElectronico::withoutGlobalScopes()->latest()->first();

        // Empresa A intenta descargar el PDF de ese documento.
        $this->crearUsuarioCliente();
        $this->get("/cliente/facturacion/{$docB->id}/pdf")->assertNotFound();
    }

    public function test_no_se_puede_facturar_ventas_de_otra_empresa(): void
    {
        // Empresa B tiene una venta sin facturar.
        $this->crearUsuarioCliente();
        $ventaB = $this->crearVentaConCaja();

        // Empresa A intenta incluir esa venta en un documento propio.
        $this->crearUsuarioCliente();
        $this->postJson('/cliente/facturacion', [
            'tipo'                       => 'dee_pos',
            'ventas_ids'                 => [$ventaB->id],
        ])->assertUnprocessable();

        // La venta de empresa B sigue sin facturar.
        $this->assertSame('sin_facturar', $ventaB->fresh()->estado_facturacion);
    }
}
