<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Comprador;
use App\Models\Cliente\DocumentoElectronico;
use App\Models\Cliente\DocumentoSoporte;
use App\Models\Cliente\Gasto;
use App\Models\Cliente\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FacturacionController extends Controller
{
    public function index(): View
    {
        $documentos = DocumentoElectronico::with('comprador', 'ventas')
            ->orderByDesc('fecha_emision')
            ->get()
            ->map(fn ($doc) => $doc->toResumenArray());

        $ventasSinFacturar = Venta::with('caja', 'comprador')
            ->noAnuladas()
            ->where('estado_facturacion', 'sin_facturar')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (Venta $v) => [
                'id'     => $v->id,
                'label'  => 'Venta #'.$v->id.' — '.$v->fecha->locale('es')->translatedFormat('d M, h:i a')
                    .($v->comprador ? ' · '.$v->comprador->nombre : ''),
                'monto'  => '$'.number_format((float) $v->total, 0, ',', '.').' · '.($v->metodo_pago === 'efectivo' ? 'Efectivo' : 'Digital'),
                'total'  => (float) $v->total,
                // Si el cliente ya pidió factura al momento de la venta,
                // el comprador queda listo para no volver a pedir estos
                // datos al emitir -ver facturacion.js (autorrelleno).
                'compradorTipoDoc' => $v->comprador?->tipo_documento,
                'compradorNumDoc'  => $v->comprador?->numero_documento,
                'compradorNombre'  => $v->comprador?->nombre,
            ]);

        $stats = $this->calcularStats($documentos);

        // --- Igual que arriba, pero del lado de los GASTOS (dinero que
        // sale): solo Arriendo va por Documento Soporte. Servicios casi
        // siempre es un proveedor formal (EPM, Claro, etc.) que YA te
        // factura a ti -ahí no se genera nada, se guarda su factura. Y
        // Nómina no es un "documento soporte" ni una factura -es un
        // sistema DIAN totalmente aparte (Nómina Electrónica), con su
        // propio módulo pendiente de construir.
        $documentosGastos = DocumentoSoporte::with('gastos')
            ->orderByDesc('fecha_emision')
            ->get()
            ->map(fn ($doc) => $doc->toResumenArray());

        $gastosSinReportar = Gasto::whereIn('categoria', ['arriendo'])
            ->where('estado_documento', 'sin_reportar')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (Gasto $g) => [
                'id'        => $g->id,
                'label'     => ucfirst($g->categoria).' — '.$g->descripcion.' — '.$g->fecha->locale('es')->translatedFormat('d M, h:i a'),
                'monto'     => '$'.number_format((float) $g->monto, 0, ',', '.'),
                'total'     => (float) $g->monto,
                'categoria' => $g->categoria,
            ]);

        $statsGastos = $this->calcularStatsGastos($documentosGastos);

        return view('cliente.facturacion', [
            'documentos'        => $documentos,
            'ventasSinFacturar' => $ventasSinFacturar,
            'stats'             => $stats,
            'documentosGastos'  => $documentosGastos,
            'gastosSinReportar' => $gastosSinReportar,
            'statsGastos'       => $statsGastos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo'         => ['required', 'in:factura_individual,factura_consolidada,dee_pos'],
            'ventas_ids'   => ['required', 'array', 'min:1'],
            'ventas_ids.*' => ['integer', 'exists:ventas,id'],
            'comprador_nombre'          => ['nullable', 'string', 'max:255'],
            'comprador_tipo_documento'  => ['nullable', 'in:CC,NIT,CE,PP'],
            'comprador_numero_documento'=> ['nullable', 'string', 'max:30'],
        ]);

        // Solo la factura individual necesita comprador identificado. La
        // consolidada es justo lo contrario: junta varias ventas de
        // consumidor final (sin nombre ni documento, igual que DEE/POS)
        // en un solo reporte para pasarle a la DIAN al cierre del día -si
        // pidiera un comprador, dejaría de ser un consolidado real.
        $necesitaComprador = $validated['tipo'] === 'factura_individual';

        if ($necesitaComprador && empty($validated['comprador_numero_documento'])) {
            return response()->json(['message' => 'Este tipo de documento requiere un comprador identificado.'], 422);
        }

        $empresaId = auth()->user()->empresa_id;

        $ventas = Venta::noAnuladas()
            ->whereIn('id', $validated['ventas_ids'])
            ->where('empresa_id', $empresaId)
            ->get();

        if ($ventas->count() !== count($validated['ventas_ids'])) {
            return response()->json(['message' => 'Una o más ventas seleccionadas no son válidas.'], 422);
        }

        $documento = DB::transaction(function () use ($validated, $ventas, $empresaId, $necesitaComprador) {
            $comprador = null;
            if ($necesitaComprador) {
                $comprador = Comprador::firstOrCreate(
                    ['numero_documento' => $validated['comprador_numero_documento']],
                    [
                        'tipo_documento' => $validated['comprador_tipo_documento'],
                        'nombre'         => $validated['comprador_nombre'],
                    ]
                );
            }

            $doc = DocumentoElectronico::create([
                'empresa_id'   => $empresaId,
                'numero'       => DocumentoElectronico::generarNumero($empresaId, $validated['tipo']),
                'tipo'         => $validated['tipo'],
                'comprador_id' => $comprador?->id,
                // CUFE simulado: en producción vendría de la API DIAN/Factus.
                'cufe'         => hash('sha384', uniqid('cufe_', true)),
                'valor_total'  => $ventas->sum('total'),
                'fecha_emision'=> now(),
            ]);

            $doc->ventas()->attach($ventas->pluck('id'));

            $nuevoEstado = match ($validated['tipo']) {
                'factura_individual'  => 'facturada_individual',
                'factura_consolidada' => 'incluida_en_consolidado',
                'dee_pos'             => 'incluida_en_consolidado',
            };

            Venta::whereIn('id', $ventas->pluck('id'))->update(['estado_facturacion' => $nuevoEstado]);

            $doc->load('comprador', 'ventas');

            return $doc;
        });

        return response()->json(['documento' => $documento->toResumenArray()]);
    }

    /**
     * PDF descargable del documento -el route-model-binding de
     * DocumentoElectronico ya pasa por el EmpresaScope, así que un
     * documento de otra empresa da 404 directo (mismo criterio que
     * VentasController::recibo()).
     */
    public function pdf(DocumentoElectronico $documento): Response
    {
        $documento->load('comprador', 'ventas.detalles.producto');
        $empresa = auth()->user()->empresa;

        // dompdf no siempre resuelve bien una URL de /storage/... -se manda
        // el logo ya embebido en base64, igual que en el recibo de Ventas.
        $logoDataUri = null;
        $logoPath = $empresa->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        $pdf = Pdf::loadView('cliente.facturacion-pdf', [
            'documento' => $documento,
            'empresa' => $empresa,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("factura-{$documento->numero}.pdf");
    }

    public function anular(DocumentoElectronico $documento): JsonResponse
    {
        if ($documento->estaAnulado()) {
            return response()->json(['message' => 'Este documento ya está anulado.'], 422);
        }

        DB::transaction(function () use ($documento) {
            $documento->update(['anulada_en' => now()]);

            // Devolver las ventas incluidas a sin_facturar para que puedan
            // volver a incluirse en un nuevo documento
            Venta::whereIn('id', $documento->ventas->pluck('id'))
                ->update(['estado_facturacion' => 'sin_facturar']);
        });

        return response()->json(['estado' => 'anulada']);
    }

    private function calcularStats(\Illuminate\Support\Collection $documentos): array
    {
        $emitidos = $documentos->where('estado', 'emitida');

        return [
            'totalFacturado'   => $emitidos->sum('valorTotal'),
            'countIndividual'  => $emitidos->where('tipo', 'factura_individual')->count(),
            'countConsolidada' => $emitidos->where('tipo', 'factura_consolidada')->count(),
            'countDeePos'      => $emitidos->where('tipo', 'dee_pos')->count(),
        ];
    }

    /* ====================================================================
     * GASTOS -espejo de lo de arriba, pero para documento soporte /
     * nómina electrónica (dinero que el negocio paga, no que recibe).
     * ==================================================================== */

    public function storeGasto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo'                          => ['required', 'in:documento_soporte'],
            'gastos_ids'                    => ['required', 'array', 'min:1'],
            'gastos_ids.*'                  => ['integer', 'exists:gastos,id'],
            'beneficiario_nombre'           => ['required', 'string', 'max:255'],
            'beneficiario_tipo_documento'   => ['nullable', 'in:CC,NIT,CE,PP'],
            'beneficiario_numero_documento' => ['nullable', 'string', 'max:30'],
        ]);

        $empresaId = auth()->user()->empresa_id;

        // Solo arriendo va por Documento Soporte -es el caso real de pagar
        // a alguien que no puede facturarte. Servicios/nómina se filtran
        // acá también, no solo en el listado de "pendientes" del index().
        $gastos = Gasto::whereIn('id', $validated['gastos_ids'])
            ->where('empresa_id', $empresaId)
            ->where('categoria', 'arriendo')
            ->get();

        if ($gastos->count() !== count($validated['gastos_ids'])) {
            return response()->json(['message' => 'Uno o más gastos seleccionados no son válidos.'], 422);
        }

        $documento = DB::transaction(function () use ($validated, $gastos, $empresaId) {
            $doc = DocumentoSoporte::create([
                'empresa_id'                    => $empresaId,
                'numero'                        => DocumentoSoporte::generarNumero($empresaId, $validated['tipo']),
                'tipo'                          => $validated['tipo'],
                'beneficiario_nombre'           => $validated['beneficiario_nombre'],
                'beneficiario_tipo_documento'   => $validated['beneficiario_tipo_documento'] ?? null,
                'beneficiario_numero_documento' => $validated['beneficiario_numero_documento'] ?? null,
                // CUFE simulado: en producción vendría de la API DIAN/Factus.
                'cufe'          => hash('sha384', uniqid('cufe_', true)),
                'valor_total'   => $gastos->sum('monto'),
                'fecha_emision' => now(),
            ]);

            $doc->gastos()->attach($gastos->pluck('id'));
            Gasto::whereIn('id', $gastos->pluck('id'))->update(['estado_documento' => 'reportado']);

            $doc->load('gastos');

            return $doc;
        });

        return response()->json(['documento' => $documento->toResumenArray()]);
    }

    /**
     * PDF descargable -mismo criterio de aislamiento que pdf() de Ventas
     * (route-model-binding + EmpresaScope).
     */
    public function pdfGasto(DocumentoSoporte $documento): Response
    {
        $documento->load('gastos');
        $empresa = auth()->user()->empresa;

        $logoDataUri = null;
        $logoPath = $empresa->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        $pdf = Pdf::loadView('cliente.facturacion-gastos-pdf', [
            'documento'   => $documento,
            'empresa'     => $empresa,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("documento-{$documento->numero}.pdf");
    }

    public function anularGasto(DocumentoSoporte $documento): JsonResponse
    {
        if ($documento->estaAnulado()) {
            return response()->json(['message' => 'Este documento ya está anulado.'], 422);
        }

        DB::transaction(function () use ($documento) {
            $documento->update(['anulada_en' => now()]);

            Gasto::whereIn('id', $documento->gastos->pluck('id'))
                ->update(['estado_documento' => 'sin_reportar']);
        });

        return response()->json(['estado' => 'anulada']);
    }

    private function calcularStatsGastos(\Illuminate\Support\Collection $documentos): array
    {
        $emitidos = $documentos->where('estado', 'emitida');

        return [
            'totalReportado' => $emitidos->sum('valorTotal'),
            'countSoporte'   => $emitidos->where('tipo', 'documento_soporte')->count(),
        ];
    }
}
