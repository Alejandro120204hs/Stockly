<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Empleado;
use App\Models\Cliente\NominaDocumento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nómina Electrónica -deliberadamente SIN cálculo legal (nada de salud,
 * pensión, SMMLV ni retención). El dueño del negocio decide cuánto le
 * paga a cada empleado; este módulo solo deja constancia de ese pago,
 * con la misma estructura simulada (CUNE, sin conexión real a Factus)
 * que el resto de Facturación.
 */
class NominaController extends Controller
{
    public function index(): View
    {
        $empleados = Empleado::withCount('documentosNomina')
            ->orderBy('nombres')
            ->get()
            ->map(fn (Empleado $e) => $this->shapeEmpleado($e));

        $documentos = NominaDocumento::with('empleado')
            ->orderByDesc('fecha_emision')
            ->get()
            ->map(fn (NominaDocumento $doc) => $doc->toResumenArray());

        $emitidos = $documentos->where('estado', 'emitida');

        $stats = [
            'empleadosActivos' => Empleado::whereNull('fecha_retiro')->count(),
            'totalPagado'      => $emitidos->sum('montoPagado'),
            'documentosCount'  => $emitidos->count(),
        ];

        return view('cliente.nomina', [
            'empleados'  => $empleados,
            'documentos' => $documentos,
            'stats'      => $stats,
        ]);
    }

    public function storeEmpleado(Request $request): JsonResponse
    {
        $validated = $this->validarEmpleado($request);

        $empleado = Empleado::create($validated);

        return response()->json(['empleado' => $this->shapeEmpleado($empleado)]);
    }

    public function updateEmpleado(Request $request, Empleado $empleado): JsonResponse
    {
        $validated = $this->validarEmpleado($request, $empleado->id);

        $empleado->update($validated);

        return response()->json(['empleado' => $this->shapeEmpleado($empleado->fresh())]);
    }

    /**
     * Soft delete -un empleado con documentos de nómina ya emitidos no
     * puede perder ese historial, igual que un proveedor con compras.
     */
    public function destroyEmpleado(Empleado $empleado): JsonResponse
    {
        $empleado->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Recibe una lista de pagos [{empleado_id, monto_pagado}] del mismo
     * período y crea un documento por cada empleado con monto > 0 -así
     * se puede pagar a varios empleados en un solo envío sin generar
     * documentos vacíos para los que no se marcaron.
     */
    public function storeDocumentos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo'                 => ['required', 'string', 'max:100'],
            'fecha_pago'               => ['required', 'date'],
            'pagos'                    => ['required', 'array', 'min:1'],
            'pagos.*.empleado_id'      => ['required', 'integer', 'exists:empleados,id'],
            'pagos.*.monto_pagado'     => ['required', 'numeric', 'min:0'],
        ]);

        $empresaId = auth()->user()->empresa_id;

        $pagos = collect($validated['pagos'])->filter(fn ($p) => (float) $p['monto_pagado'] > 0);

        if ($pagos->isEmpty()) {
            return response()->json(['message' => 'Escribe al menos un monto mayor a cero para pagar.'], 422);
        }

        $empleadoIds = $pagos->pluck('empleado_id')->all();
        $empleados = Empleado::whereIn('id', $empleadoIds)
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy('id');

        if ($empleados->count() !== count(array_unique($empleadoIds))) {
            return response()->json(['message' => 'Uno o más empleados seleccionados no son válidos.'], 422);
        }

        $documentos = DB::transaction(function () use ($pagos, $empresaId, $validated) {
            return $pagos->map(function ($pago) use ($empresaId, $validated) {
                return NominaDocumento::create([
                    'empresa_id'    => $empresaId,
                    'empleado_id'   => $pago['empleado_id'],
                    'numero'        => NominaDocumento::generarNumero($empresaId),
                    // CUNE simulado: en producción vendría de la API DIAN/Factus.
                    'cune'          => hash('sha384', uniqid('cune_', true)),
                    'periodo'       => $validated['periodo'],
                    'monto_pagado'  => $pago['monto_pagado'],
                    'fecha_pago'    => $validated['fecha_pago'],
                    'fecha_emision' => now(),
                ]);
            });
        });

        $documentos->each(fn (NominaDocumento $doc) => $doc->load('empleado'));

        return response()->json(['documentos' => $documentos->map(fn (NominaDocumento $doc) => $doc->toResumenArray())->values()]);
    }

    /**
     * PDF descargable -el route-model-binding de NominaDocumento pasa por
     * el EmpresaScope, así que un documento de otra empresa da 404 directo
     * (mismo criterio que FacturacionController::pdf()/pdfGasto()).
     */
    public function pdfDocumento(NominaDocumento $documento): Response
    {
        $documento->load('empleado');
        $empresa = auth()->user()->empresa;

        $logoDataUri = null;
        $logoPath = $empresa->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        $pdf = Pdf::loadView('cliente.nomina-pdf', [
            'documento'   => $documento,
            'empresa'     => $empresa,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("nomina-{$documento->numero}.pdf");
    }

    public function anularDocumento(NominaDocumento $documento): JsonResponse
    {
        if ($documento->estaAnulado()) {
            return response()->json(['message' => 'Este documento ya está anulado.'], 422);
        }

        $documento->update(['anulada_en' => now()]);

        return response()->json(['estado' => 'anulada']);
    }

    private function validarEmpleado(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'in:CC,CE,PP'],
            'numero_documento' => [
                'required', 'string', 'max:30',
                // Único POR EMPRESA, no global -la misma persona podría
                // trabajar (o haber trabajado) en dos negocios distintos
                // que ambos usan Stockly.
                Rule::unique('empleados', 'numero_documento')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($ignorarId),
            ],
            'cargo' => ['nullable', 'string', 'max:255'],
            'salario' => ['nullable', 'numeric', 'min:0'],
            'fecha_retiro' => ['nullable', 'date'],
        ]);
    }

    private function shapeEmpleado(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'nombres' => $empleado->nombres,
            'apellidos' => $empleado->apellidos,
            'nombreCompleto' => $empleado->nombreCompleto(),
            'tipoDocumento' => $empleado->tipo_documento,
            'numeroDocumento' => $empleado->numero_documento,
            'cargo' => $empleado->cargo,
            'salario' => $empleado->salario !== null ? (float) $empleado->salario : null,
            'fechaRetiro' => $empleado->fecha_retiro?->format('Y-m-d'),
            'activo' => $empleado->estaActivo(),
            'documentosCount' => $empleado->documentos_nomina_count,
        ];
    }
}
