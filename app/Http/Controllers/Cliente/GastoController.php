<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use App\Models\Cliente\Gasto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoController extends Controller
{
    public function index(): View
    {
        $gastos = Gasto::with('usuario', 'caja')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (Gasto $gasto) => $this->shapeGasto($gasto));

        // "Hoy" es desde que se abrió el turno actual, no medianoche real
        // -mismo criterio que el Dashboard (ver Caja::inicioDeHoy()). "Este
        // mes" sí se queda en mes calendario -un turno que cruza la
        // medianoche del último día del mes es un caso tan raro que no
        // vale la pena la complejidad de manejarlo ahí también.
        $inicioHoy = Caja::inicioDeHoy();
        $inicioMes = now()->startOfMonth();

        return view('cliente.gastos', [
            'gastos' => $gastos,
            'gastosHoy' => (float) Gasto::where('fecha', '>=', $inicioHoy)->sum('monto'),
            'gastosMes' => (float) Gasto::where('fecha', '>=', $inicioMes)->sum('monto'),
            'cantidadGastos' => $gastos->count(),
            'fechaHoyTurno' => $inicioHoy->toDateString(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // 'nomina' ya no es una categoría elegible acá -pagarle a un
            // empleado ahora tiene su propio módulo (Nómina), con
            // documento real para la DIAN en vez de un simple gasto. Los
            // gastos ya registrados con esa categoría se conservan tal
            // cual, solo se cierra la puerta a crear NUEVOS así.
            'categoria' => ['required', 'in:arriendo,servicios,otros'],
            'descripcion' => ['required', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['required', 'in:efectivo,efectivo_externo,digital,digital_externo'],
        ]);

        // Mismo criterio que en Compras: solo "efectivo" (caja física) y
        // "digital" (lo digital de hoy) descuentan de la caja actual, así
        // que necesitan una abierta. Las variantes "_externo" son plata
        // que nunca fue parte de esta caja -pueden registrarse sin una.
        $requiereCaja = in_array($validated['metodo_pago'], ['efectivo', 'digital'], true);
        $cajaAbierta = $requiereCaja ? Caja::whereNull('cierre_en')->first() : null;

        if ($requiereCaja && ! $cajaAbierta) {
            return response()->json(['message' => 'Debes abrir la caja antes de registrar un gasto con plata de hoy.'], 422);
        }

        $gasto = Gasto::create([
            'caja_id' => $cajaAbierta?->id,
            'usuario_id' => auth()->id(),
            'categoria' => $validated['categoria'],
            'descripcion' => $validated['descripcion'],
            'responsable' => $validated['responsable'] ?? null,
            'monto' => $validated['monto'],
            'metodo_pago' => $validated['metodo_pago'],
            'fecha' => now(),
        ]);

        $gasto->load('usuario', 'caja');

        return response()->json(['gasto' => $this->shapeGasto($gasto)]);
    }

    private function shapeGasto(Gasto $gasto): array
    {
        return [
            'id' => $gasto->id,
            'fecha' => $gasto->fecha->locale('es')->translatedFormat('d M Y'),
            'fechaTurno' => $gasto->fechaTurno(),
            'hora' => hora_es($gasto->fecha),
            'categoria' => $gasto->categoria,
            'descripcion' => $gasto->descripcion,
            'responsable' => $gasto->responsable,
            'monto' => (float) $gasto->monto,
            'metodo' => $gasto->metodo_pago,
            'registradoPor' => $gasto->usuario->nombreCompleto(),
        ];
    }
}
