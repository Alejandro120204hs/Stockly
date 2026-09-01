<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Gasto;
use App\Models\Cliente\NominaDocumento;
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

    /**
     * Reporte de UN día puntual, elegido con el selector de calendario
     * -no es uno de los 4 períodos fijos, así que se calcula bajo demanda
     * en vez de venir precargado en el HTML. La gráfica de barras acá es
     * por HORA, no por día: un solo día siempre da una sola barra "por
     * día", que se ve vacía sin importar el ancho -por hora sí cuenta algo
     * real (a qué horas vende más el negocio).
     */
    public function dia(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $fecha = Carbon::parse($validated['fecha']);
        $data = $this->buildPeriodo($fecha->copy()->startOfDay(), $fecha->copy()->endOfDay(), false, true);

        return response()->json($data);
    }

    /**
     * Reporte de UN mes puntual, elegido con el selector de mes -igual que
     * dia(), no es uno de los 4 períodos fijos así que se calcula bajo
     * demanda. A diferencia de "Este mes" (que siempre es el mes actual),
     * acá puede pedirse cualquier mes ya pasado. La gráfica sigue siendo
     * por día (barrasDiarias), no mensual -eso es solo para "Este año".
     */
    public function mes(Request $request)
    {
        $validated = $request->validate([
            'mes' => ['required', 'date_format:Y-m'],
        ]);

        $inicio = Carbon::createFromFormat('Y-m', $validated['mes'])->startOfMonth();

        if ($inicio->greaterThan(now()->startOfMonth())) {
            return response()->json(['message' => 'No puedes pedir el reporte de un mes futuro.'], 422);
        }

        $hasta = $inicio->isSameMonth(now()) ? now() : $inicio->copy()->endOfMonth();
        $data = $this->buildPeriodo($inicio, $hasta, false);

        return response()->json($data);
    }

    public function pdf(Request $request): Response
    {
        $fecha = $request->input('fecha');
        $mes = $request->input('mes');

        if ($fecha) {
            $fechaCarbon = Carbon::parse($fecha);
            $data = $this->buildPeriodo($fechaCarbon->copy()->startOfDay(), $fechaCarbon->copy()->endOfDay(), false, true);
            $periodoLabel = $fechaCarbon->locale('es')->translatedFormat('d \d\e F \d\e Y');
            $nombreArchivo = 'reporte-'.$fechaCarbon->format('Ymd');
        } elseif ($mes) {
            $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
            $hasta = $inicio->isSameMonth(now()) ? now() : $inicio->copy()->endOfMonth();
            $data = $this->buildPeriodo($inicio, $hasta, false);
            $periodoLabel = ucfirst($inicio->locale('es')->translatedFormat('F Y'));
            $nombreArchivo = 'reporte-'.$inicio->format('Ym');
        } else {
            $periodoKey = array_key_exists($request->input('periodo', 'semana'), self::PERIODOS)
                ? $request->input('periodo')
                : 'semana';

            [$desde, $hasta] = $this->rangoParaPeriodo($periodoKey);
            $data = $this->buildPeriodo($desde, $hasta, $periodoKey === 'anio');
            $periodoLabel = self::PERIODOS[$periodoKey];
            $nombreArchivo = 'reporte-'.str_replace(' ', '-', strtolower($periodoLabel)).'-'.now()->format('Ymd');
        }

        $empresa = auth()->user()->empresa;

        $pdf = Pdf::loadView('cliente.reportes-pdf', [
            'data'         => $data,
            'periodoLabel' => $periodoLabel,
            'empresa'      => $empresa,
            'generadoEl'   => now()->locale('es')->translatedFormat('d \d\e F Y, h:i a'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($nombreArchivo.'.pdf');
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

    private function buildPeriodo(Carbon $desde, Carbon $hasta, bool $mensual = false, bool $porHora = false): array
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

        // Los pagos de Nómina viven en su propia tabla (no son un Gasto) y
        // no están ligados a ninguna caja -son plata que sale igual, así
        // que Reportes sí los resta de la ganancia neta (el Dashboard no,
        // porque "hoy" ahí es específicamente lo que pasó por la caja).
        $nominaPagada = (float) NominaDocumento::whereBetween('fecha_pago', [$desdeStr, $hastaStr])
            ->whereNull('anulada_en')
            ->sum('monto_pagado');

        // Ganancia bruta: usa los precios HISTÓRICOS congelados en venta_detalle
        // (no el precio_costo actual del producto — puede haber cambiado).
        $gananciaBruta = (float) $ventas->sum(fn ($v) => $v->gananciaBruta());
        $totalIngresos = (float) $ventas->sum('total');
        // Reportes sí incluye TODOS los gastos (de caja y "aparte"), a diferencia
        // del Dashboard que solo resta los gastos pagados de la caja del día.
        $totalGastos  = (float) $gastos->sum('monto') + $nominaPagada;
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

        $graficaBars = match (true) {
            $porHora => $this->barrasPorHora($desde, $ventas),
            $mensual => $this->barrasMensuales($ventas),
            default  => $this->barrasDiarias($desde, $hasta, $ventas),
        };

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
                // Suma tanto los gastos históricos con categoría "nomina"
                // (de antes de que existiera el módulo real) como los
                // documentos de Nómina emitidos de verdad.
                'nomina'    => ($gastosCat['nomina'] ?? 0) + $nominaPagada,
                'arriendo'  => $gastosCat['arriendo']  ?? 0,
                'servicios' => $gastosCat['servicios'] ?? 0,
                'otros'     => $gastosCat['otros']     ?? 0,
            ],
            'graficaBars'  => $graficaBars,
            'fechaLabel'   => $desde->locale('es')->translatedFormat('d \d\e F'),
            // Solo para el PDF de un día puntual: reemplaza a "Ventas por
            // período" (que en un solo día da 24 filas casi todas en $0)
            // por el detalle real de cada venta -sí tiene sentido en un
            // documento pensado como comprobante impreso del día.
            'ventasDetalle' => $porHora
                ? $ventas->sortBy('fecha')->map(fn (Venta $v) => [
                    'hora'      => hora_es($v->fecha),
                    'productos' => $v->detalles->map(fn ($d) => $d->producto->nombre.' x'.$d->cantidad)->implode(', '),
                    'total'     => (float) $v->total,
                    'metodo'    => $v->metodo_pago === 'efectivo' ? 'Efectivo' : 'Digital',
                ])->values()->all()
                : null,
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

    /**
     * 24 barras, una por hora del día (0-23) -a diferencia de las demás
     * gráficas, acá se agrupa por la hora REAL de la venta, no por
     * fechaTurno(): dentro de un solo día, lo que importa es a qué hora
     * del reloj entró cada venta, sin importar cuándo se abrió la caja.
     */
    private function barrasPorHora(Carbon $desde, $ventas): array
    {
        $horaActual = now()->isSameDay($desde) ? now()->hour : -1;

        return collect(range(0, 23))->map(function (int $hora) use ($ventas, $horaActual) {
            $sufijo = $hora < 12 ? 'a' : 'p';
            $hora12 = $hora % 12 === 0 ? 12 : $hora % 12;

            return [
                'label' => $hora12.$sufijo,
                'total' => (float) $ventas->filter(fn ($v) => (int) $v->fecha->format('G') === $hora)->sum('total'),
                'esHoy' => $hora === $horaActual,
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
