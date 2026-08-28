<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Gasto;
use App\Models\Cliente\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportesController extends Controller
{
    private const PERIODOS = [
        'hoy'    => 'Hoy',
        'semana' => 'Esta semana',
        'mes'    => 'Este mes',
        'anio'   => 'Este año',
    ];

    public function index(): View
    {
        $data = [];
        foreach (array_keys(self::PERIODOS) as $key) {
            [$desde, $hasta] = $this->rangoParaPeriodo($key);
            $data[$key] = $this->buildPeriodo($desde, $hasta, $key === 'anio');
        }

        return view('cliente.reportes', [
            'reportesJson' => json_encode($data),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $periodoKey = array_key_exists($request->input('periodo', 'semana'), self::PERIODOS)
            ? $request->input('periodo')
            : 'semana';

        [$desde, $hasta] = $this->rangoParaPeriodo($periodoKey);
        $data    = $this->buildPeriodo($desde, $hasta, $periodoKey === 'anio');
        $empresa = auth()->user()->empresa;

        $pdf = Pdf::loadView('cliente.reportes-pdf', [
            'data'         => $data,
            'periodoLabel' => self::PERIODOS[$periodoKey],
            'empresa'      => $empresa,
            'generadoEl'   => now()->locale('es')->translatedFormat('d \d\e F Y, h:i a'),
        ])->setPaper('a4', 'portrait');

        $filename = 'reporte-'.str_replace(' ', '-', strtolower(self::PERIODOS[$periodoKey])).'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    private function rangoParaPeriodo(string $periodo): array
    {
        return match ($periodo) {
            // "Hoy" empieza cuando abrió el turno actual, no a medianoche.
            // Así una venta de la 1am (misma caja abierta desde ayer)
            // sigue contando como "hoy". Ver Caja::inicioDeHoy().
            'hoy'    => [Caja::inicioDeHoy(), now()],
            'semana' => [now()->startOfWeek(Carbon::MONDAY), now()],
            'mes'    => [now()->startOfMonth(), now()],
            'anio'   => [now()->startOfYear(), now()],
        };
    }

    private function buildPeriodo(Carbon $desde, Carbon $hasta, bool $mensual = false): array
    {
        $desdeStr = $desde->toDateString();
        $hastaStr = $hasta->toDateString();

        // Cargamos 'caja' en eager para poder llamar fechaTurno() en PHP
        // sin disparar N+1 queries. Luego filtramos en PHP porque fechaTurno()
        // no es una columna real — es la fecha de apertura de la caja del turno.
        //
        // El filtro excluye registros cuya caja abrió ANTES del inicio del
        // período (turno cruzando medianoche en el borde del rango). No
        // necesitamos expandir el rango DB — si venta.fecha < $desde, su
        // fechaTurno() tampoco puede ser >= $desdeStr (porque la caja siempre
        // abre antes que la venta).
        $ventas = Venta::with(['caja', 'detalles.producto'])
            ->noAnuladas()
            ->whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->filter(fn ($v) => $v->fechaTurno() >= $desdeStr && $v->fechaTurno() <= $hastaStr)
            ->values();

        $gastos = Gasto::with('caja')
            ->whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->filter(fn ($g) => $g->fechaTurno() >= $desdeStr && $g->fechaTurno() <= $hastaStr)
            ->values();

        // Ganancia bruta: usa los precios HISTÓRICOS congelados en venta_detalle
        // (no el precio_costo actual del producto — puede haber cambiado).
        $gananciaBruta = (float) $ventas->sum(fn ($v) => $v->gananciaBruta());
        $totalIngresos = (float) $ventas->sum('total');
        // Reportes sí incluye TODOS los gastos (de caja y "aparte"), a diferencia
        // del Dashboard que solo resta los gastos pagados de la caja del día.
        $totalGastos  = (float) $gastos->sum('monto');
        $gananciaNeta = $gananciaBruta - $totalGastos;

        $pagoEfectivo = (float) $ventas->where('metodo_pago', 'efectivo')->sum('total');
        $pagoDigital  = (float) $ventas->where('metodo_pago', 'digital')->sum('total');

        $topProductos = $ventas->flatMap(fn ($v) => $v->detalles)
            ->groupBy('producto_id')
            ->map(fn ($grupo) => [
                'nombre'   => optional($grupo->first()->producto)->nombre ?? '—',
                'cantidad' => (int) $grupo->sum('cantidad'),
                'ingresos' => (float) $grupo->sum(fn ($d) => $d->cantidad * (float) $d->precio_unitario_venta),
            ])
            ->sortByDesc('cantidad')
            ->take(8)
            ->values()
            ->all();

        $gastosCat = $gastos->groupBy('categoria')->map(fn ($g) => (float) $g->sum('monto'));

        $graficaBars = $mensual
            ? $this->barrasMensuales($ventas)
            : $this->barrasDiarias($desde, $hasta, $ventas);

        return [
            'ingresos'         => $totalIngresos,
            'gananciaBruta'    => $gananciaBruta,
            'gastos'           => $totalGastos,
            'gananciaNeta'     => $gananciaNeta,
            'cantidadVentas'   => $ventas->count(),
            'pagoEfectivo'     => $pagoEfectivo,
            'pagoDigital'      => $pagoDigital,
            'topProductos'     => $topProductos,
            'gastosCategorias' => [
                'nomina'    => $gastosCat['nomina']    ?? 0,
                'arriendo'  => $gastosCat['arriendo']  ?? 0,
                'servicios' => $gastosCat['servicios'] ?? 0,
                'otros'     => $gastosCat['otros']     ?? 0,
            ],
            'graficaBars' => $graficaBars,
        ];
    }

    private function barrasDiarias(Carbon $desde, Carbon $hasta, $ventas): array
    {
        $dias = max(1, (int) $desde->diffInDays($hasta) + 1);
        $dias = min($dias, 31);

        return collect(range($dias - 1, 0))->map(function (int $i) use ($hasta, $ventas) {
            $fecha = $hasta->copy()->subDays($i)->toDateString();

            return [
                'label' => Carbon::parse($fecha)->locale('es')->translatedFormat('d M'),
                // Agrupa por fechaTurno, no por fecha — un turno que cruza
                // medianoche no parte sus ventas entre dos barras distintas.
                'total' => (float) $ventas->filter(fn ($v) => $v->fechaTurno() === $fecha)->sum('total'),
                'esHoy' => $fecha === now()->toDateString(),
            ];
        })->all();
    }

    private function barrasMensuales($ventas): array
    {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return collect(range(1, now()->month))->map(function (int $m) use ($ventas, $meses) {
            return [
                'label' => $meses[$m - 1],
                'total' => (float) $ventas->filter(fn ($v) => Carbon::parse($v->fechaTurno())->month === $m)->sum('total'),
                'esHoy' => $m === now()->month,
            ];
        })->all();
    }
}
