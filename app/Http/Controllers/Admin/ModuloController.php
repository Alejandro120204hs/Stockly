<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\View\View;

/**
 * Catálogo de módulos -de solo lectura. No hay precio por módulo: el
 * cobro es por plan (mensual/trimestral/semestral/anual), no por módulo
 * activo (ver Pagos y suscripciones). Prender/apagar Facturación
 * electrónica se hace desde el panel de cada empresa
 * (Admin\EmpresaController::modulos()); acá solo se ve el desglose real.
 *
 * Solo hay dos módulos, complementarios entre sí (toda empresa cae en
 * exactamente uno): "Solo Administración" -el sistema completo, sin
 * Factus- y "Administración con Factus" -lo mismo, más facturación
 * electrónica real a la DIAN. Las dos etiquetas dejan claro que TODAS las
 * empresas tienen Administración siempre; lo único que varía es si además
 * tienen Factus encima.
 */
class ModuloController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::orderBy('nombre_negocio')->get();
        $total = $empresas->count();

        $definiciones = [
            [
                'id'          => 'administracion',
                'nombre'      => 'Solo Administración',
                'descripcion' => 'Inventario, ventas, caja, gastos, proveedores y nómina -sin emitir documentos electrónicos a la DIAN.',
                'condicion'   => fn (Empresa $e) => ! $e->tiene_facturacion,
            ],
            [
                'id'          => 'administracion_factus',
                'nombre'      => 'Administración con Factus',
                'descripcion' => 'Todo lo de Administración, más facturación electrónica y documento soporte emitidos de verdad a la DIAN.',
                'condicion'   => fn (Empresa $e) => (bool) $e->tiene_facturacion,
            ],
        ];

        $modulos = collect($definiciones)->map(function (array $def) use ($empresas, $total) {
            $empresasModulo = $empresas->map(fn (Empresa $e) => [
                'nombre' => $e->nombre_negocio,
                'activo' => $def['condicion']($e),
            ])->values();

            $activas = $empresasModulo->where('activo', true)->count();

            return [
                'id'          => $def['id'],
                'nombre'      => $def['nombre'],
                'descripcion' => $def['descripcion'],
                'empresas'    => $empresasModulo,
                'activas'     => $activas,
                'total'       => $total,
                'pct'         => $total > 0 ? (int) round(($activas / $total) * 100) : 0,
            ];
        });

        return view('admin.modulos', [
            'modulos'       => $modulos,
            'totalEmpresas' => $total,
        ]);
    }
}
