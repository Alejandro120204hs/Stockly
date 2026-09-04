<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Mail\PagoReportado;
use App\Models\PagoSuscripcion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * No hay pasarela de pagos -el cliente ve los planes, transfiere por
 * fuera del sistema (Nequi/llave) y sube el comprobante. Queda
 * 'pago_recibido' hasta que el admin lo revisa y aprueba o rechaza desde
 * Admin\PagoController. Esta es la ÚNICA ruta /cliente/* que queda fuera
 * del middleware "suscripcion" (ver routes/web.php y
 * App\Http\Middleware\EnsureSuscripcionActiva) -si no, una empresa
 * bloqueada nunca podría llegar aquí a desbloquearse.
 */
class SuscripcionController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->empresa;

        $ultimoPago = PagoSuscripcion::where('empresa_id', $empresa->id)
            ->latest('fecha_pago')
            ->first();

        // El plan activo AHORA MISMO -distinto de $ultimoPago, que puede
        // ser un reporte pendiente o rechazado más reciente que la
        // última activación real.
        $planActual = PagoSuscripcion::where('empresa_id', $empresa->id)
            ->where('estado', 'activado')
            ->latest('fecha_activacion')
            ->first();

        $diasRestantes = $empresa->fecha_vencimiento ? now()->startOfDay()->diffInDays($empresa->fecha_vencimiento) : null;

        $estadoLabels = [
            'activado' => 'Activado',
            'pago_recibido' => 'Pendiente',
            'rechazado' => 'Rechazado',
        ];

        $historial = PagoSuscripcion::where('empresa_id', $empresa->id)
            ->orderByDesc('fecha_pago')
            ->get()
            ->map(fn (PagoSuscripcion $p) => [
                'id' => $p->id,
                'plan' => PagoSuscripcion::PLANES[$p->plan]['label'] ?? $p->plan,
                'monto' => $p->monto !== null ? (float) $p->monto : null,
                'metodo' => $p->metodo,
                'fecha' => $p->fecha_pago->locale('es')->translatedFormat('d M Y'),
                'estado' => $p->estado,
                'estadoLabel' => $estadoLabels[$p->estado] ?? $p->estado,
            ]);

        return view('cliente.suscripcion', [
            'empresa' => $empresa,
            'estado' => $empresa->estadoEfectivo(),
            'pagoPendiente' => $ultimoPago?->estado === 'pago_recibido' ? $ultimoPago : null,
            'motivoRechazo' => $ultimoPago?->estado === 'rechazado' ? $ultimoPago->motivo_rechazo : null,
            'planes' => PagoSuscripcion::PLANES,
            'planActual' => $planActual,
            'diasRestantes' => $diasRestantes,
            'historial' => $historial,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;

        $yaHayPendiente = PagoSuscripcion::where('empresa_id', $empresa->id)
            ->where('estado', 'pago_recibido')
            ->exists();

        if ($yaHayPendiente) {
            return back()->with('status', 'pago-ya-pendiente');
        }

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(PagoSuscripcion::PLANES))],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $pago = PagoSuscripcion::create([
            'empresa_id' => $empresa->id,
            'plan' => $validated['plan'],
            'monto' => PagoSuscripcion::PLANES[$validated['plan']]['precio'],
            'metodo' => 'Nequi',
            'estado' => 'pago_recibido',
            'comprobante_path' => $request->file('comprobante')->store('pagos/comprobantes', 'public'),
            'fecha_pago' => now(),
        ]);

        $this->avisarAdmins($pago);

        return back()->with('status', 'pago-reportado');
    }

    /**
     * El aviso por correo es un extra, no el objetivo -si el SMTP falla,
     * el pago ya reportado por el cliente NO debe perderse por eso.
     */
    private function avisarAdmins(PagoSuscripcion $pago): void
    {
        try {
            $admins = User::whereHas('rol', fn ($q) => $q->where('nombre', 'admin'))->get();

            foreach ($admins as $admin) {
                Mail::to($admin->correo)->send(new PagoReportado($pago));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
