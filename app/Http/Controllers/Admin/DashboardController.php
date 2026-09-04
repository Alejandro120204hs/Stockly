<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::all();
        $porEstado = $empresas->countBy(fn (Empresa $e) => $e->estadoEfectivo());

        // Solo pagos YA activados -uno 'pago_recibido' (reportado por el
        // cliente, pendiente de validar) todavía no es un ingreso real.
        $ingresosMes = (float) PagoSuscripcion::where('estado', 'activado')
            ->whereYear('fecha_activacion', now()->year)
            ->whereMonth('fecha_activacion', now()->month)
            ->sum('monto');

        $proximasAVencer = $empresas
            ->filter(fn (Empresa $e) => $e->estadoEfectivo() === 'por_vencer')
            ->sortBy('fecha_vencimiento')
            ->take(5)
            ->map(fn (Empresa $e) => [
                'nombre' => $e->nombre_negocio,
                // Días CALENDARIO (medianoche a medianoche), no el tiempo
                // exacto transcurrido -si son las 11pm y vence pasado
                // mañana a medianoche, para el usuario eso sigue siendo
                // "2 días", no "1" por la diferencia de horas.
                'dias'   => (int) now()->startOfDay()->diffInDays($e->fecha_vencimiento),
                'fecha'  => $e->fecha_vencimiento->format('d/m/Y'),
            ])
            ->values();

        $activacionesRecientes = PagoSuscripcion::where('estado', 'activado')
            ->with('empresa')
            ->latest('fecha_activacion')
            ->take(5)
            ->get()
            ->map(fn (PagoSuscripcion $p) => [
                'empresa' => $p->empresa?->nombre_negocio ?? '—',
                'plan'    => PagoSuscripcion::PLANES[$p->plan]['label'] ?? $p->plan,
                'monto'   => $p->monto !== null ? (float) $p->monto : null,
                'hace'    => $p->fecha_activacion->diffForHumans(),
            ]);

        return view('admin.dashboard-admin', [
            'empresasActivas'       => $porEstado->get('activo', 0),
            'porVencer'             => $porEstado->get('por_vencer', 0),
            'vencidas'              => $porEstado->get('vencido', 0),
            'suspendidas'           => $porEstado->get('suspendido', 0),
            'ingresosMes'           => $ingresosMes,
            'ingresosUltimos6Meses' => $this->ingresosUltimos6Meses(),
            'proximasAVencer'       => $proximasAVencer,
            'activacionesRecientes' => $activacionesRecientes,
            'totalEmpresas'         => $empresas->count(),
            'countFacturacion'      => $empresas->where('tiene_facturacion', true)->count(),
            'countAdministracion'   => $empresas->where('tiene_facturacion', false)->count(),
        ]);
    }

    private function ingresosUltimos6Meses(): array
    {
        $meses = collect(range(5, 0))->map(fn (int $i) => now()->copy()->subMonths($i));

        $totalPorMes = $meses->map(function (Carbon $mes) {
            return (float) PagoSuscripcion::where('estado', 'activado')
                ->whereYear('fecha_activacion', $mes->year)
                ->whereMonth('fecha_activacion', $mes->month)
                ->sum('monto');
        });

        $maxTotal = max($totalPorMes->max(), 1);

        return $meses->map(function (Carbon $mes, int $i) use ($totalPorMes, $maxTotal) {
            $total = $totalPorMes[$i];

            return [
                'label'   => ucfirst($mes->locale('es')->translatedFormat('M')),
                'total'   => $total,
                'pct'     => (int) round(($total / $maxTotal) * 100),
                'esActual'=> $mes->isSameMonth(now()),
            ];
        })->all();
    }
}
