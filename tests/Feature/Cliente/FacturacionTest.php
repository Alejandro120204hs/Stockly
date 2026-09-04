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
 * Reglas de negocio del módulo de Facturación (fuera del aislamiento
 * multi-tenant, que vive en FacturacionMultiTenantTest):
 *
 *  - "Individual" es la única que pide un comprador identificado.
 *  - "Consolidada" es justo lo contrario a lo que se construyó al
 *    principio: junta varias ventas de consumidor final (sin nombre ni
 *    documento) en un solo reporte para la DIAN, igual que DEE/POS -no
 *    "varias ventas de un mismo cliente identificado".
 */
class FacturacionTest extends TestCase
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

    private function crearVentaConCaja(): Venta
    {
        if (! \App\Models\Cliente\Caja::whereNull('cierre_en')->exists()) {
            $this->postJson('/cliente/caja/abrir', ['base_inicial' => 0])->assertOk();
        }

        $nombre = 'Producto Test '.uniqid();
        $this->postJson('/cliente/inventario/productos', [
            'nombre'        => $nombre,
            'categoria'     => 'General',
            'precio_costo'  => 10000,
            'precio_venta'  => 20000,
            'unidad_medida' => 'Unidad',
        ])->assertOk();
        $prod = Producto::where('nombre', $nombre)->firstOrFail();
        $this->postJson('/cliente/inventario/compras', [
            'tipo' => 'informal', 'factura_validada' => false, 'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1, 'costo' => 10000]],
        ])->assertOk();
        $this->postJson('/cliente/inventario/transferencias', ['producto_id' => $prod->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/cliente/ventas', [
            'metodo_pago' => 'efectivo', 'monto_recibido' => 20000,
            'lineas' => [['producto_id' => $prod->id, 'cantidad' => 1]],
        ])->assertOk();

        return Venta::noAnuladas()->where('estado_facturacion', 'sin_facturar')->orderByDesc('id')->firstOrFail();
    }

    public function test_una_factura_individual_requiere_comprador_identificado(): void
    {
        $this->crearUsuarioCliente();
        $venta = $this->crearVentaConCaja();

        $response = $this->postJson('/cliente/facturacion', [
            'tipo'       => 'factura_individual',
            'ventas_ids' => [$venta->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_una_factura_consolidada_no_requiere_comprador_y_agrupa_ventas_de_consumidor_final(): void
    {
        $this->crearUsuarioCliente();
        $venta1 = $this->crearVentaConCaja();
        $venta2 = $this->crearVentaConCaja();

        $response = $this->postJson('/cliente/facturacion', [
            'tipo'       => 'factura_consolidada',
            'ventas_ids' => [$venta1->id, $venta2->id],
        ]);

        $response->assertOk();
        $doc = DocumentoElectronico::firstOrFail();
        $this->assertNull($doc->comprador_id);
        $this->assertCount(2, $doc->ventas);
        $this->assertSame(40000.0, (float) $doc->valor_total);

        // Las dos ventas quedan marcadas como incluidas en el consolidado.
        $this->assertSame('incluida_en_consolidado', $venta1->fresh()->estado_facturacion);
        $this->assertSame('incluida_en_consolidado', $venta2->fresh()->estado_facturacion);
    }

    public function test_una_factura_consolidada_puede_incluir_solo_las_ventas_que_el_usuario_elija(): void
    {
        $this->crearUsuarioCliente();
        $venta1 = $this->crearVentaConCaja();
        $venta2 = $this->crearVentaConCaja();

        // Solo se elige la primera -la segunda debe seguir sin facturar.
        $this->postJson('/cliente/facturacion', [
            'tipo'       => 'factura_consolidada',
            'ventas_ids' => [$venta1->id],
        ])->assertOk();

        $this->assertSame('incluida_en_consolidado', $venta1->fresh()->estado_facturacion);
        $this->assertSame('sin_facturar', $venta2->fresh()->estado_facturacion);
    }

    public function test_dee_pos_tampoco_requiere_comprador(): void
    {
        $this->crearUsuarioCliente();
        $venta = $this->crearVentaConCaja();

        $response = $this->postJson('/cliente/facturacion', [
            'tipo'       => 'dee_pos',
            'ventas_ids' => [$venta->id],
        ]);

        $response->assertOk();
        $this->assertNull(DocumentoElectronico::firstOrFail()->comprador_id);
    }
}
