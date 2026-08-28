<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Proveedor;
use App\Models\Cliente\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresaId = auth()->user()->empresa_id;
        $hoy = now()->toDateString();

        // Venta y Caja ya aplican el EmpresaScope solas -pero gastos se
        // consulta directo con DB::table() (todavía no tiene modelo propio,
        // eso llega con el backend de Gastos), así que ESTA sí necesita el
        // filtro de empresa a mano para no filtrar datos de otro negocio.
        // noAnuladas() -una venta cancelada no cuenta como venta real para
        // ningún total del negocio, aunque el historial de Ventas la siga
        // mostrando marcada.
        $ventasHoy = Venta::with('detalles.producto', 'comprador')->noAnuladas()->whereDate('fecha', $hoy)->get();
        $gananciaBrutaHoy = (float) $ventasHoy->sum(fn (Venta $venta) => $venta->gananciaBruta());

        $gastosHoy = (float) DB::table('gastos')
            ->where('empresa_id', $empresaId)
            ->whereDate('fecha', $hoy)
            ->sum('monto');

        $cajaAbierta = Caja::whereNull('cierre_en')->first();

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
