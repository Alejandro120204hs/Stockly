<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Comprador;
use App\Models\Cliente\DocumentoElectronico;
use App\Models\Cliente\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacturacionController extends Controller
{
    public function index(): View
    {
        $documentos = DocumentoElectronico::with('comprador', 'ventas')
            ->orderByDesc('fecha_emision')
            ->get()
            ->map(fn ($doc) => $doc->toResumenArray());

        $ventasSinFacturar = Venta::with('caja')
            ->noAnuladas()
            ->where('estado_facturacion', 'sin_facturar')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (Venta $v) => [
                'id'     => $v->id,
                'label'  => 'Venta #'.$v->id.' — '.$v->fecha->locale('es')->translatedFormat('d M, h:i a'),
                'monto'  => '$'.number_format((float) $v->total, 0, ',', '.').' · '.($v->metodo_pago === 'efectivo' ? 'Efectivo' : 'Digital'),
                'total'  => (float) $v->total,
            ]);

        $stats = $this->calcularStats($documentos);

        return view('cliente.facturacion', [
            'documentos'        => $documentos,
            'ventasSinFacturar' => $ventasSinFacturar,
            'stats'             => $stats,
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

        // Individual y consolidada necesitan comprador identificado
        $necesitaComprador = in_array($validated['tipo'], ['factura_individual', 'factura_consolidada'], true);

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
}
