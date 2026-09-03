<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PagoSuscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Historial de pagos y suscripciones. La activación manual (sin que el
 * cliente reporte nada) se sigue haciendo desde el panel de cada empresa
 * (Admin\EmpresaController::activar()) y nace ya 'activado'. Pero ahora
 * también existen pagos 'pago_recibido' -el cliente los reporta con
 * comprobante desde /cliente/suscripcion
 * (App\Http\Controllers\Cliente\SuscripcionController)- que este
 * controlador aprueba o rechaza.
 */
class PagoController extends Controller
{
    private const ESTADO_LABELS = [
        'pago_recibido' => 'Pendiente',
        'activado'      => 'Activado',
        'rechazado'     => 'Rechazado',
    ];

    public function index(): View
    {
        $todos = PagoSuscripcion::with(['empresa', 'usuarioActivador'])
            ->latest('fecha_pago')
            ->get();

        $pagos = $todos->map(fn (PagoSuscripcion $p) => $this->shapePago($p));

        $activados = $todos->where('estado', 'activado');

        return view('admin.pagos', [
            'pagos'             => $pagos,
            'totalActivaciones' => $activados->count(),
            'pendientes'        => $todos->where('estado', 'pago_recibido')->count(),
            'ingresosTotal'     => (float) $activados->sum('monto'),
            'ingresosMes'       => (float) $activados
                ->filter(fn (PagoSuscripcion $p) => $p->fecha_activacion
                    && $p->fecha_activacion->year === now()->year
                    && $p->fecha_activacion->month === now()->month)
                ->sum('monto'),
        ]);
    }

    /**
     * Mismas reglas ya probadas en Admin\EmpresaController::activar() (ver
     * Empresa::calcularNuevoVencimiento()), solo que acá se aprueba un pago
     * que el cliente YA reportó en vez de crear uno nuevo.
     */
    public function aprobar(PagoSuscripcion $pago): JsonResponse
    {
        if ($pago->estado !== 'pago_recibido') {
            return response()->json(['message' => 'Este pago ya fue revisado.'], 422);
        }

        DB::transaction(function () use ($pago) {
            $empresa = $pago->empresa;
            $vencimientoAnterior = $empresa->fecha_vencimiento;
            $vencimientoNuevo = $empresa->calcularNuevoVencimiento($pago->plan);

            $empresa->update([
                'estado_suscripcion' => 'activo',
                'fecha_vencimiento'  => $vencimientoNuevo,
            ]);

            $pago->update([
                'estado'               => 'activado',
                'fecha_activacion'     => now(),
                'vencimiento_anterior' => $vencimientoAnterior,
                'vencimiento_nuevo'    => $vencimientoNuevo,
                'usuario_activador_id' => auth()->id(),
            ]);
        });

        return response()->json(['pago' => $this->shapePago($pago->fresh()->load(['empresa', 'usuarioActivador']))]);
    }

    public function rechazar(Request $request, PagoSuscripcion $pago): JsonResponse
    {
        if ($pago->estado !== 'pago_recibido') {
            return response()->json(['message' => 'Este pago ya fue revisado.'], 422);
        }

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $pago->update([
            'estado'         => 'rechazado',
            'motivo_rechazo' => $validated['motivo'],
        ]);

        return response()->json(['pago' => $this->shapePago($pago->fresh()->load(['empresa', 'usuarioActivador']))]);
    }

    private function shapePago(PagoSuscripcion $p): array
    {
        return [
            'id'                  => $p->id,
            'empresa'             => $p->empresa?->nombre_negocio ?? '—',
            'plan'                => PagoSuscripcion::PLANES[$p->plan]['label'] ?? $p->plan,
            'monto'               => $p->monto !== null ? (float) $p->monto : null,
            'metodo'              => $p->metodo,
            'estado'              => $p->estado,
            'estadoLabel'         => self::ESTADO_LABELS[$p->estado] ?? $p->estado,
            'comprobanteUrl'      => $p->comprobanteUrl(),
            'motivoRechazo'       => $p->motivo_rechazo,
            'fechaPago'           => $p->fecha_pago?->locale('es')->translatedFormat('d M Y, g:i a'),
            'fechaActivacion'     => $p->fecha_activacion?->locale('es')->translatedFormat('d M Y, g:i a'),
            'vencimientoAnterior' => $p->vencimiento_anterior?->format('d/m/Y') ?? 'Sin activación previa',
            'vencimientoNuevo'    => $p->vencimiento_nuevo?->format('d/m/Y'),
            'activadoPor'         => $p->usuarioActivador?->nombreCompleto() ?? '—',
        ];
    }
}
