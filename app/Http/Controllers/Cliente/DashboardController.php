<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Gasto;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Proveedor;
use App\Models\Cliente\Venta;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $cajaAbierta = Caja::whereNull('cierre_en')->first();

        // "Hoy" es desde que abriste tu turno actual, no medianoche real
        // -así un negocio que cierra pasada la medianoche no ve sus
        // números resetearse a mitad de turno.
        $inicioHoy = $cajaAbierta ? $cajaAbierta->apertura_en->copy() : now()->startOfDay();

        // noAnuladas() -una venta cancelada no cuenta como venta real para
        // ningún total del negocio, aunque el historial de Ventas la siga
        // mostrando marcada.
        $ventasHoy = Venta::with('detalles.producto', 'comprador', 'caja')->noAnuladas()->where('fecha', '>=', $inicioHoy)->get();
        $gananciaBrutaHoy = (float) $ventasHoy->sum(fn (Venta $venta) => $venta->gananciaBruta());

        // La Ganancia neta del Dashboard es "la ganancia de la caja de
        // hoy" -solo resta los gastos pagados con la caja de hoy (efectivo
        // o digital "de hoy"). Los gastos "aparte" (pagados con plata que
        // no era de esta caja) sí son reales y sí restan ganancia, pero
        // eso se ve en Reportes -acá se mostraría el mismo día en que se
        // pagaron, aunque la plata en realidad viene de otro momento, y
        // eso confundía más de lo que aclaraba.
        $gastosHoy = (float) Gasto::where('fecha', '>=', $inicioHoy)
            ->whereIn('metodo_pago', ['efectivo', 'digital'])
            ->sum('monto');

        $productosVenta = Producto::with('inventarioVitrina')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => (float) $producto->precio_venta,
                'stockVitrina' => $producto->stockVitrina(),
            ]);

        // Mismo par de datos que ya usa el modal "Registrar compra" en
        // Inventario -acá también se incluye ese modal, para que el
        // acceso rápido del Dashboard lo abra al instante en vez de
        // llevar a otra página.
        $productosCompra = Producto::orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precioCosto' => (float) $producto->precio_costo,
            ]);

        $proveedores = Proveedor::orderBy('nombre')->get(['id', 'nombre']);

        return view('cliente.dashboard-cliente', [
            'totalVentasHoy' => (float) $ventasHoy->sum('total'),
            'cantidadVentasHoy' => $ventasHoy->count(),
            'gananciaBrutaHoy' => $gananciaBrutaHoy,
            'gananciaNetaHoy' => $gananciaBrutaHoy - $gastosHoy,
            'cajaAbierta' => $cajaAbierta ? [
                'id' => $cajaAbierta->id,
                'baseInicial' => (float) $cajaAbierta->base_inicial,
                'horaApertura' => hora_es($cajaAbierta->apertura_en),
            ] : null,
            'ventasSemana' => $this->shapeVentasSemana(),
            // Forma completa (Venta::toResumenArray) para que, al hacer
            // click en una de estas filas, se pueda abrir el mismo panel
            // de detalle que ya existe en Ventas -no solo lo mínimo para
            // pintar la fila.
            'ventasRecientes' => $ventasHoy->sortByDesc('fecha')->take(10)->map(fn (Venta $venta) => $venta->toResumenArray())->values(),
            'productosVenta' => $productosVenta,
            'productosCompra' => $productosCompra,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Lunes a domingo de la semana actual, con el total vendido cada día
     * y el porcentaje relativo al día más fuerte de la semana (para la
     * altura de la barra) -"esHoy" marca la columna a resaltar.
     */
    private function shapeVentasSemana(): array
    {
        $inicioSemana = now()->startOfWeek(Carbon::MONDAY);
        $finSemana = now()->endOfWeek(Carbon::SUNDAY);

        $ventas = Venta::noAnuladas()->whereBetween('fecha', [$inicioSemana, $finSemana])->get();

        $etiquetas = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        $dias = collect(range(0, 6))->map(function (int $i) use ($inicioSemana, $ventas, $etiquetas) {
            $fecha = $inicioSemana->copy()->addDays($i);

            return [
                'label' => $etiquetas[$i],
                'total' => (float) $ventas->filter(fn (Venta $venta) => $venta->fecha->isSameDay($fecha))->sum('total'),
                'esHoy' => $fecha->isToday(),
            ];
        });

        $maxTotal = max($dias->max('total'), 1);

        return $dias->map(fn (array $dia) => [
            'label' => $dia['label'],
            'total' => $dia['total'],
            'pct' => (int) round(($dia['total'] / $maxTotal) * 100),
            'valor' => '$'.number_format($dia['total'], 0, ',', '.'),
            'esHoy' => $dia['esHoy'],
        ])->all();
    }
}
