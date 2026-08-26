<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Compra;
use App\Models\Cliente\CompraDetalle;
use App\Models\Cliente\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::withCount('compras')
            ->with([
                'compras' => fn ($query) => $query->orderByDesc('fecha'),
                'compras.facturaValidada',
                'compras.detalles.producto',
            ])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Proveedor $proveedor) => $this->shapeProveedor($proveedor));

        return view('cliente.proveedores', [
            'proveedores' => $proveedores,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validarProveedor($request);

        $proveedor = Proveedor::create($validated);
        $proveedor->loadCount('compras')->load(['compras.facturaValidada', 'compras.detalles.producto']);

        return response()->json(['proveedor' => $this->shapeProveedor($proveedor)]);
    }

    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validated = $this->validarProveedor($request, $proveedor->id);

        $proveedor->update($validated);
        $proveedor->loadCount('compras')->load(['compras.facturaValidada', 'compras.detalles.producto']);

        return response()->json(['proveedor' => $this->shapeProveedor($proveedor)]);
    }

    /**
     * No se puede borrar un proveedor con compras ya registradas -esas
     * compras necesitan seguir mostrando de dónde vinieron en el
     * historial, igual que una categoría con productos.
     */
    public function destroy(Proveedor $proveedor): JsonResponse
    {
        if ($proveedor->compras()->exists()) {
            return response()->json(['message' => 'No puedes eliminar un proveedor que ya tiene compras registradas.'], 422);
        }

        $proveedor->delete();

        return response()->json(['ok' => true]);
    }

    private function validarProveedor(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'nit' => [
                'required', 'string', 'max:20',
                Rule::unique('proveedores', 'nit')->ignore($ignorarId),
            ],
            'dv' => ['nullable', 'string', 'max:2'],
            'tipo_persona' => ['required', 'in:natural,juridica'],
            'regimen_fiscal' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Misma forma (camelCase) que el resto del panel cliente.
     */
    private function shapeProveedor(Proveedor $proveedor): array
    {
        return [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'nit' => $proveedor->nit,
            'dv' => $proveedor->dv,
            'tipoPersona' => $proveedor->tipo_persona,
            'regimenFiscal' => $proveedor->regimen_fiscal,
            'telefono' => $proveedor->telefono,
            'correo' => $proveedor->correo,
            'direccion' => $proveedor->direccion,
            'departamento' => $proveedor->departamento,
            'ciudad' => $proveedor->ciudad,
            'comprasCount' => $proveedor->compras_count,
            'totalComprado' => (float) $proveedor->compras->sum('total'),
            'compras' => $proveedor->compras->map(fn (Compra $compra) => $this->shapeCompra($compra))->all(),
        ];
    }

    /**
     * Mismo detalle que ya se ve en el panel de Inventario (Compras) -así
     * el historial de un proveedor puede abrir el mismo panel de detalle
     * sin necesidad de una segunda llamada al servidor.
     */
    private function shapeCompra(Compra $compra): array
    {
        $facturaEstado = 'sin_factura';
        if ($compra->tipo === 'con_factura') {
            $facturaEstado = $compra->factura_validada_id ? 'validada' : 'por_validar';
        }

        return [
            'id' => $compra->id,
            'fecha' => $compra->fecha->locale('es')->translatedFormat('d M Y, g:i a'),
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
