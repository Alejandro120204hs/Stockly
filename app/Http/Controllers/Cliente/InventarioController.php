<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\CategoriaProducto;
use App\Models\Cliente\Compra;
use App\Models\Cliente\CompraDetalle;
use App\Models\Cliente\FacturaProveedorValidada;
use App\Models\Cliente\InventarioBodega;
use App\Models\Cliente\InventarioVitrina;
use App\Models\Cliente\MovimientoTransferencia;
use App\Models\Cliente\Producto;
use App\Models\Cliente\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    /**
     * Unidades de medida sugeridas por defecto -no es una tabla propia, es
     * solo el punto de partida para el selector. El campo `unidad_medida`
     * en la base de datos es texto libre a propósito: el negocio puede no
     * ser una licorera (fruver, ferretería, ropa...) y necesitar unidades
     * distintas ("Libra", "Metro", "Par"), por eso el selector siempre
     * tiene "+ Agregar otra..." para escribir la que haga falta.
     */
    private const UNIDADES_BASE = ['Botella', 'Lata', 'Caja', 'Paquete', 'Unidad', 'Kilogramo', 'Litro'];

    public function index()
    {
        $productos = Producto::with(['categoria', 'inventarioVitrina', 'inventarioBodega'])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto) => $this->shapeProducto($producto));

        $categorias = CategoriaProducto::orderBy('nombre')->pluck('nombre');

        // Las unidades personalizadas que este negocio ya haya usado antes
        // (agregadas con "+ Agregar otra...") también quedan disponibles la
        // próxima vez, igual que pasa con las categorías.
        $unidadesPersonalizadas = Producto::query()
            ->whereNotIn('unidad_medida', self::UNIDADES_BASE)
            ->distinct()
            ->orderBy('unidad_medida')
            ->pluck('unidad_medida');

        $unidades = collect(self::UNIDADES_BASE)->merge($unidadesPersonalizadas);

        $comprasOrdenadas = Compra::with(['proveedor', 'facturaValidada', 'detalles.producto'])
            ->orderByDesc('fecha')
            ->get();

        $compras = $comprasOrdenadas->map(fn (Compra $compra) => $this->shapeCompra($compra));

        $comprasEsteMes = $comprasOrdenadas
            ->filter(fn (Compra $compra) => $compra->fecha->isCurrentMonth())
            ->count();

        // Solo id+nombre -los datos fiscales completos del proveedor se
        // gestionan en su propio módulo (/cliente/proveedores), acá solo
        // hace falta poder elegir uno para "Registrar compra".
        $proveedores = Proveedor::orderBy('nombre')->get(['id', 'nombre']);

        return view('cliente.inventario', [
            'productos' => $productos,
            'categorias' => $categorias,
            'compras' => $compras,
            'unidades' => $unidades,
            'comprasEsteMes' => $comprasEsteMes,
            'proveedores' => $proveedores,
        ]);
    }

    public function storeCategoria(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $yaExiste = CategoriaProducto::whereRaw('LOWER(nombre) = ?', [mb_strtolower($validated['nombre'])])->exists();

        if ($yaExiste) {
            return response()->json(['message' => 'Ya existe una categoría con ese nombre.'], 422);
        }

        $categoria = CategoriaProducto::create(['nombre' => $validated['nombre']]);

        return response()->json(['categoria' => $categoria->nombre]);
    }

    public function storeProducto(Request $request): JsonResponse
    {
        $validated = $this->validarProducto($request);

        $producto = DB::transaction(function () use ($validated) {
            $categoria = CategoriaProducto::firstOrCreate(['nombre' => $validated['categoria']]);

            $producto = Producto::create([
                'categoria_id' => $categoria->id,
                'nombre' => $validated['nombre'],
                'precio_costo' => $validated['precio_costo'],
                'precio_venta' => $validated['precio_venta'],
                'unidad_medida' => $validated['unidad_medida'],
            ]);

            InventarioVitrina::create(['producto_id' => $producto->id, 'stock' => 0]);
            InventarioBodega::create(['producto_id' => $producto->id, 'stock' => 0]);

            return $producto;
        });

        $producto->load(['categoria', 'inventarioVitrina', 'inventarioBodega']);

        return response()->json(['producto' => $this->shapeProducto($producto)]);
    }

    public function updateProducto(Request $request, Producto $producto): JsonResponse
    {
        $validated = $this->validarProducto($request);

        $categoria = CategoriaProducto::firstOrCreate(['nombre' => $validated['categoria']]);

        $producto->update([
            'categoria_id' => $categoria->id,
            'nombre' => $validated['nombre'],
            'precio_costo' => $validated['precio_costo'],
            'precio_venta' => $validated['precio_venta'],
            'unidad_medida' => $validated['unidad_medida'],
        ]);

        $producto->load(['categoria', 'inventarioVitrina', 'inventarioBodega']);

        return response()->json(['producto' => $this->shapeProducto($producto)]);
    }

    /**
     * Baja lógica (soft delete): el producto deja de aparecer en el
     * catálogo, pero las compras/movimientos que ya lo mencionan siguen
     * intactos en el historial -no se puede borrar de verdad sin romper
     * esos registros (compra_detalle no tiene cascada hacia productos).
     */
    public function destroyProducto(Producto $producto): JsonResponse
    {
        $producto->delete();

        return response()->json(['ok' => true]);
    }

    public function updateCategoria(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre_actual' => ['required', 'string'],
            'nombre_nuevo' => ['required', 'string', 'max:255'],
        ]);

        $categoria = CategoriaProducto::where('nombre', $validated['nombre_actual'])->firstOrFail();

        $yaExiste = CategoriaProducto::whereRaw('LOWER(nombre) = ?', [mb_strtolower($validated['nombre_nuevo'])])
            ->where('id', '!=', $categoria->id)
            ->exists();

        if ($yaExiste) {
            return response()->json(['message' => 'Ya existe una categoría con ese nombre.'], 422);
        }

        $categoria->update(['nombre' => $validated['nombre_nuevo']]);

        return response()->json(['categoria' => $categoria->nombre]);
    }

    public function destroyCategoria(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string'],
        ]);

        $categoria = CategoriaProducto::where('nombre', $validated['nombre'])->firstOrFail();

        if ($categoria->productos()->exists()) {
            return response()->json(['message' => 'No puedes eliminar una categoría que todavía tiene productos asignados.'], 422);
        }

        $categoria->delete();

        return response()->json(['ok' => true]);
    }

    public function storeCompra(Request $request): JsonResponse
    {
        // Los "exists" van escopados a la empresa del usuario -de lo
        // contrario, la regla corre una consulta cruda sin pasar por el
        // Global Scope, y aceptaría un id de producto/proveedor de OTRA
        // empresa como si fuera válido.
        $empresaId = auth()->user()->empresa_id;

        $validated = $request->validate([
            'tipo' => ['required', 'in:proveedor,informal'],
            'proveedor_id' => [
                'nullable', 'required_if:tipo,proveedor', 'integer',
                Rule::exists('proveedores', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
            ],
            'cufe' => ['nullable', 'string', 'max:255'],
            'factura_validada' => ['required', 'boolean'],
            'metodo_pago' => ['required', 'in:efectivo,efectivo_externo,digital,digital_externo'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => [
                'required', 'integer',
                Rule::exists('productos', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
            ],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
            'lineas.*.costo' => ['required', 'numeric', 'min:0'],
        ]);

        // "efectivo" (del cajón físico) y "digital" (de lo digital recibido
        // hoy) descuentan del cierre de la caja actual, así que ambos
        // necesitan una caja abierta y quedan asociados a ella.
        // "efectivo_externo" y "digital_externo" son plata que nunca fue
        // parte de esta caja (ahorros, otro momento) -no descuentan nada y
        // pueden registrarse sin caja abierta.
        $requiereCaja = in_array($validated['metodo_pago'], ['efectivo', 'digital'], true);
        $cajaAbierta = $requiereCaja ? Caja::whereNull('cierre_en')->first() : null;

        if ($requiereCaja && ! $cajaAbierta) {
            return response()->json(['message' => 'Debes abrir la caja antes de registrar una compra con plata de hoy.'], 422);
        }

        $compra = DB::transaction(function () use ($validated, $cajaAbierta) {
            $proveedor = null;
            $facturaValidada = null;
            $total = collect($validated['lineas'])->sum(fn ($linea) => $linea['cantidad'] * $linea['costo']);

            if ($validated['tipo'] === 'proveedor') {
                $proveedor = Proveedor::findOrFail($validated['proveedor_id']);

                // Solo se crea el registro de factura validada si de verdad
                // se validó -una factura "por validar" no es todavía una
                // factura validada real, así que no le corresponde una fila
                // en facturas_proveedor_validadas.
                if ($validated['factura_validada'] && ! empty($validated['cufe'])) {
                    $facturaValidada = FacturaProveedorValidada::create([
                        'proveedor_id' => $proveedor->id,
                        'cufe' => $validated['cufe'],
                        'fecha_emision' => now()->toDateString(),
                        'valor_total' => $total,
                        'estado' => 'vigente',
                    ]);
                }
            }

            $compra = Compra::create([
                'caja_id' => $cajaAbierta?->id,
                'proveedor_id' => $proveedor?->id,
                'factura_validada_id' => $facturaValidada?->id,
                'tipo' => $validated['tipo'] === 'proveedor' ? 'con_factura' : 'sin_factura',
                'metodo_pago' => $validated['metodo_pago'],
                'total' => $total,
                'usuario_id' => auth()->id(),
                'fecha' => now(),
            ]);

            foreach ($validated['lineas'] as $linea) {
                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'costo_unitario' => $linea['costo'],
                ]);

                // Toda compra suma a BODEGA -nunca a vitrina directamente.
                $inventarioBodega = InventarioBodega::firstOrCreate(
                    ['producto_id' => $linea['producto_id']],
                    ['stock' => 0]
                );
                $inventarioBodega->increment('stock', $linea['cantidad']);
            }

            return $compra;
        });

        $compra->load(['proveedor', 'facturaValidada', 'detalles.producto']);

        $productosIds = collect($validated['lineas'])->pluck('producto_id');
        $productosActualizados = Producto::with(['inventarioVitrina', 'inventarioBodega'])
            ->whereIn('id', $productosIds)
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'stockVitrina' => $producto->stockVitrina(),
                'stockBodega' => $producto->stockBodega(),
            ]);

        return response()->json([
            'compra' => $this->shapeCompra($compra),
            'productosActualizados' => $productosActualizados,
        ]);
    }

    public function transferir(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto = Producto::with(['inventarioVitrina', 'inventarioBodega'])->findOrFail($validated['producto_id']);

        if ($validated['cantidad'] > $producto->stockBodega()) {
            return response()->json(['message' => 'No puedes transferir más de lo que hay en bodega.'], 422);
        }

        DB::transaction(function () use ($producto, $validated) {
            $producto->inventarioBodega->decrement('stock', $validated['cantidad']);

            $vitrina = InventarioVitrina::firstOrCreate(['producto_id' => $producto->id], ['stock' => 0]);
            $vitrina->increment('stock', $validated['cantidad']);

            MovimientoTransferencia::create([
                'producto_id' => $producto->id,
                'direccion' => 'bodega_a_vitrina',
                'cantidad' => $validated['cantidad'],
                'usuario_id' => auth()->id(),
                'fecha' => now(),
            ]);
        });

        $producto->refresh();

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'stockVitrina' => $producto->stockVitrina(),
                'stockBodega' => $producto->stockBodega(),
            ],
        ]);
    }

    /**
     * @return array{nombre: string, categoria: string, precio_costo: float, precio_venta: float, unidad_medida: string}
     */
    private function validarProducto(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'precio_costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'unidad_medida' => ['required', 'string', 'max:100'],
        ]);
    }

    /**
     * Misma forma (camelCase) que ya espera el JS del panel de Inventario,
     * para no tener que reescribir esa lógica de frontend.
     */
    private function shapeProducto(Producto $producto): array
    {
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'categoria' => $producto->categoria?->nombre,
            'precioCosto' => (float) $producto->precio_costo,
            'precioVenta' => (float) $producto->precio_venta,
            'unidad' => $producto->unidad_medida,
            'stockVitrina' => $producto->stockVitrina(),
            'stockBodega' => $producto->stockBodega(),
        ];
    }

    private function shapeCompra(Compra $compra): array
    {
        $facturaEstado = 'sin_factura';
        if ($compra->tipo === 'con_factura') {
            $facturaEstado = $compra->factura_validada_id ? 'validada' : 'por_validar';
        }

        return [
            'id' => $compra->id,
            'fecha' => $compra->fecha->locale('es')->translatedFormat('d M Y, g:i a'),
            'tipo' => $compra->tipo === 'con_factura' ? 'proveedor' : 'informal',
            'metodo' => $compra->metodo_pago,
            'proveedor' => $compra->proveedor?->nombre,
            'facturaEstado' => $facturaEstado,
            'cufe' => $compra->facturaValidada?->cufe,
            'lineas' => $compra->detalles->map(fn (CompraDetalle $detalle) => [
                'productoId' => $detalle->producto_id,
                'nombre' => $detalle->producto->nombre,
                'cantidad' => $detalle->cantidad,
                'costo' => (float) $detalle->costo_unitario,
            ])->all(),
            'total' => (float) $compra->total,
        ];
    }
}
