<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Comprador;
use App\Models\Cliente\LoteInventario;
use App\Models\Cliente\PagoEfectivo;
use App\Models\Cliente\PagoPasarela;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Venta;
use App\Models\Cliente\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class VentasController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['detalles.producto', 'comprador', 'caja'])
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (Venta $venta) => $venta->toResumenArray());

        // El buscador de "Nueva venta" muestra TODO el catálogo (igual que
        // "Registrar compra" en Inventario) -si el stock no alcanza, se
        // avisa al agregar la línea o al registrar, no se esconde el
        // producto de la búsqueda.
        $productosVenta = Producto::with('inventarioVitrina')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => (float) $producto->precio_venta,
                'stockVitrina' => $producto->stockVitrina(),
            ]);

        return view('cliente.ventas', [
            'ventas' => $ventas,
            'productosVenta' => $productosVenta,
            // El filtro de fecha por defecto es el "hoy" de turno, no
            // medianoche real -mismo criterio que el Dashboard (ver
            // Caja::inicioDeHoy()), para que al entrar a Ventas ya se vean
            // filtradas las del turno actual, sin importar si cruzó la
            // medianoche.
            'fechaHoyTurno' => Caja::inicioDeHoy()->toDateString(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $validated = $request->validate([
            'metodo_pago' => ['required', 'in:efectivo,digital'],
            'monto_recibido' => ['nullable', 'required_if:metodo_pago,efectivo', 'numeric', 'min:0'],
            'pago_confirmado' => ['nullable', 'boolean'],
            'quiere_factura' => ['nullable', 'boolean'],
            'comprador_nombre' => ['required_if:quiere_factura,true', 'nullable', 'string', 'max:255'],
            'comprador_tipo_documento' => ['required_if:quiere_factura,true', 'nullable', 'in:CC,NIT'],
            'comprador_numero_documento' => ['required_if:quiere_factura,true', 'nullable', 'string', 'max:30'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => [
                'required', 'integer',
                Rule::exists('productos', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
            ],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $productoIds = collect($validated['lineas'])->pluck('producto_id');

        // La venta SIEMPRE descuenta de vitrina -nunca de bodega. Se
        // valida el stock disponible antes de tocar nada, igual que
        // "Transferir" en Inventario valida contra el stock de bodega.
        $productos = Producto::with('inventarioVitrina')->whereIn('id', $productoIds)->get()->keyBy('id');

        foreach ($validated['lineas'] as $linea) {
            $producto = $productos[$linea['producto_id']];
            if ($linea['cantidad'] > $producto->stockVitrina()) {
                return response()->json([
                    'message' => "No hay suficiente stock en vitrina de \"{$producto->nombre}\" (disponible: {$producto->stockVitrina()}).",
                ], 422);
            }
        }

        // La caja es una SESIÓN (abrir -> cerrar), no un día calendario -si
        // no hay una abierta ahora mismo, no hay dónde contar el efectivo
        // de esta venta. Una venta digital sí puede pasar sin caja abierta
        // -no mueve efectivo físico, solo no queda asociada a ninguna caja.
        $cajaAbierta = Caja::whereNull('cierre_en')->first();

        if ($validated['metodo_pago'] === 'efectivo' && ! $cajaAbierta) {
            return response()->json(['message' => 'Debes abrir la caja antes de registrar una venta en efectivo.'], 422);
        }

        $confirmado = $validated['metodo_pago'] === 'efectivo' || (bool) ($validated['pago_confirmado'] ?? false);

        $venta = DB::transaction(function () use ($validated, $productos, $confirmado, $cajaAbierta) {
            // El precio de venta y de costo se toman del catálogo ACTUAL
            // en este momento, nunca de lo que mande el cliente -esa
            // "foto" es justo lo que después no cambia aunque el producto
            // suba o baje de precio (ver venta_detalle.precio_unitario_*).
            $total = collect($validated['lineas'])->sum(
                fn ($linea) => $linea['cantidad'] * (float) $productos[$linea['producto_id']]->precio_venta
            );

            // Todavía no existe integración real con Factus -esto NO
            // genera ningún documento DIAN de verdad. Por ahora se simula
            // que la factura queda lista de inmediato cuando hay
            // comprador, para poder ver el flujo completo en la interfaz
            // (mismo criterio que la confirmación simulada de Wompi).
            // Cuando se conecte Factus, esto se reemplaza por el estado
            // real que devuelva la API en vez de asumirlo aquí.
            $comprador = null;
            if (! empty($validated['quiere_factura'])) {
                $comprador = Comprador::firstOrCreate(
                    ['numero_documento' => $validated['comprador_numero_documento']],
                    [
                        'tipo_documento' => $validated['comprador_tipo_documento'],
                        'nombre' => $validated['comprador_nombre'],
                    ]
                );
            }

            $venta = Venta::create([
                'usuario_id' => auth()->id(),
                'caja_id' => $cajaAbierta?->id,
                'comprador_id' => $comprador?->id,
                'total' => $total,
                'metodo_pago' => $validated['metodo_pago'],
                'estado_pago' => $confirmado ? 'pagada' : 'pendiente',
                'estado_facturacion' => $comprador ? 'facturada_individual' : 'sin_facturar',
                'fecha' => now(),
            ]);

            foreach ($validated['lineas'] as $linea) {
                $producto = $productos[$linea['producto_id']];

                // FIFO: se descuenta primero del lote más viejo en
                // vitrina, y la ganancia de cada porción se calcula con el
                // costo REAL de ese lote -no con precio_costo del
                // producto, que es solo el valor mostrado por defecto en
                // el catálogo. Si la venta abarca varios lotes con costo
                // distinto, quedan varias líneas para el mismo producto
                // (el recibo las agrupa de vuelta, ver Venta::toResumenArray()).
                $consumo = LoteInventario::consumirFifo($producto->id, 'cantidad_vitrina', $linea['cantidad']);

                foreach ($consumo as $item) {
                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $producto->id,
                        'lote_inventario_id' => $item['lote']->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario_venta' => $producto->precio_venta,
                        'precio_unitario_costo' => $item['costoUnitario'],
                    ]);
                }

                $producto->inventarioVitrina->decrement('stock', $linea['cantidad']);
            }

            if ($validated['metodo_pago'] === 'efectivo') {
                PagoEfectivo::create([
                    'venta_id' => $venta->id,
                    'monto_recibido' => $validated['monto_recibido'],
                    'cambio' => max(0, $validated['monto_recibido'] - $total),
                ]);
            } else {
                PagoPasarela::create([
                    'venta_id' => $venta->id,
                    'pasarela' => 'wompi',
                    'monto' => $total,
                    // Todavía no hay integración real con la pasarela (eso
                    // llega con el webhook de verdad) -por ahora se marca
                    // "confirmado" solo si el frontend ya simuló la
                    // confirmación del QR, igual que hace hoy la demo.
                    'estado' => $confirmado ? 'confirmado' : 'pendiente',
                    'fecha_confirmacion' => $confirmado ? now() : null,
                ]);
            }

            return $venta;
        });

        $venta->load('detalles.producto', 'comprador', 'caja');

        $productosActualizados = Producto::with('inventarioVitrina')
            ->whereIn('id', $productoIds)
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'stockVitrina' => $producto->stockVitrina(),
            ]);

        return response()->json([
            'venta' => $venta->toResumenArray(),
            'productosActualizados' => $productosActualizados,
        ]);
    }

    /**
     * Anula una venta -NO la borra (queda el rastro de que existió y se
     * canceló, con la fecha), pero el stock vendido vuelve a vitrina y la
     * venta sale de los totales del Dashboard/Reportes. No se puede
     * anular dos veces la misma. No existe "editar" una venta a propósito
     * -si el cajero se equivocó, se anula esta y se registra una nueva
     * correcta, igual que hacen la mayoría de sistemas de punto de venta.
     */
    public function anular(Venta $venta): JsonResponse
    {
        if ($venta->estaAnulada()) {
            return response()->json(['message' => 'Esta venta ya está anulada.'], 422);
        }

        $venta->load('detalles.producto.inventarioVitrina', 'detalles.loteInventario', 'caja');

        DB::transaction(function () use ($venta) {
            foreach ($venta->detalles as $detalle) {
                $detalle->producto->inventarioVitrina->increment('stock', $detalle->cantidad);

                // Regresa las unidades a su lote de origen -si no se hace,
                // el lote quedaría "corto" y una venta futura de ese
                // producto podría fallar por falta de stock en lotes,
                // aunque el total agregado de vitrina sí alcance.
                $detalle->loteInventario?->increment('cantidad_vitrina', $detalle->cantidad);
            }

            $venta->update(['anulada_en' => now()]);
        });

        $productosActualizados = $venta->detalles
            ->map(fn (VentaDetalle $detalle) => [
                'id' => $detalle->producto->id,
                'stockVitrina' => $detalle->producto->stockVitrina(),
            ])
            ->unique('id')
            ->values();

        return response()->json([
            'venta' => $venta->toResumenArray(),
            'productosActualizados' => $productosActualizados,
        ]);
    }

    /**
     * Recibo interno de la venta, SIN ningún valor fiscal ante la DIAN -es
     * solo un comprobante en PDF para el cliente, independiente de si la
     * venta va a terminar en factura individual o en el consolidado del
     * día. El route-model-binding de Venta ya pasa por el EmpresaScope,
     * así que una venta de otra empresa da 404 directo.
     */
    public function recibo(Venta $venta): Response
    {
        $venta->load('detalles.producto', 'comprador', 'empresa', 'pagoEfectivo', 'pagoPasarela');

        // dompdf no siempre resuelve bien una URL de /storage/... -se
        // manda la imagen ya embebida en base64 para que el PDF nunca
        // dependa de que el proceso que lo genera pueda alcanzar esa URL.
        $logoDataUri = null;
        $logoPath = $venta->empresa->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        $pdf = Pdf::loadView('cliente.ventas.recibo-pdf', ['venta' => $venta, 'logoDataUri' => $logoDataUri]);

        return $pdf->download("recibo-venta-{$venta->id}.pdf");
    }
}
