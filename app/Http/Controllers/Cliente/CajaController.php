<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente\Caja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index()
    {
        // Todo el historial va al cliente (mismo patrón que Facturación/
        // Proveedores) -la tabla pagina y filtra por mes en el navegador,
        // no aquí. "Últimos 6" (para el stat y el gráfico) sigue siendo
        // los primeros 6 de esta misma lista, ya viene ordenada desc.
        $cierres = Caja::with('usuarioApertura')
            ->whereNotNull('cierre_en')
            ->orderByDesc('cierre_en')
            ->get()
            ->map(fn (Caja $caja) => $this->shapeCierre($caja));

        $diasSinCuadrar = $cierres->take(6)->filter(fn (array $c) => $c['diferencia'] !== 0.0 || $c['diferenciaDigital'] !== 0.0)->count();

        $cajaAbierta = Caja::with('usuarioApertura')->whereNull('cierre_en')->first();
        $cajaAbiertaData = $cajaAbierta ? $this->shapeCajaAbierta($cajaAbierta) : null;

        // Si ninguna caja está abierta, la última caja registrada se puede
        // reabrir -pero solo mientras siga siendo la última (en cuanto se
        // abre una caja nueva, la anterior queda bloqueada como historial).
        $ultimaCaja = ! $cajaAbierta ? Caja::orderByDesc('id')->first() : null;

        return view('cliente.caja', [
            'cierres' => $cierres,
            'cajaAbierta' => $cajaAbiertaData,
            'diasSinCuadrar' => $diasSinCuadrar,
            'ultimaCajaId' => $ultimaCaja?->id,
        ]);
    }

    public function abrir(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_inicial' => ['required', 'numeric', 'min:0'],
        ]);

        if (Caja::whereNull('cierre_en')->exists()) {
            return response()->json(['message' => 'Ya hay una caja abierta.'], 422);
        }

        $caja = Caja::create([
            'usuario_apertura_id' => auth()->id(),
            'base_inicial' => $validated['base_inicial'],
            'apertura_en' => now(),
        ]);

        $caja->load('usuarioApertura');

        return response()->json(['caja' => $this->shapeCajaAbierta($caja)]);
    }

    public function cerrar(Request $request, Caja $caja): JsonResponse
    {
        if (! $caja->estaAbierta()) {
            return response()->json(['message' => 'Esta caja ya está cerrada.'], 422);
        }

        $validated = $request->validate([
            'conteo_fisico' => ['required', 'numeric', 'min:0'],
            'conteo_digital' => ['required', 'numeric', 'min:0'],
        ]);

        $totales = $this->calcularTotales($caja);

        $caja->update([
            'cierre_en' => now(),
            'usuario_cierre_id' => auth()->id(),
            'conteo_fisico' => $validated['conteo_fisico'],
            'diferencia' => $validated['conteo_fisico'] - $totales['totalEsperado'],
            'conteo_digital' => $validated['conteo_digital'],
            'diferencia_digital' => $validated['conteo_digital'] - $totales['totalEsperadoDigital'],
        ]);

        return response()->json(['cierre' => $this->shapeCierre($caja)]);
    }

    /**
     * Solo se puede reabrir mientras siga siendo la caja MÁS RECIENTE de la
     * empresa -en cuanto se abrió una caja nueva después, esta ya quedó
     * como historial en firme. Al reabrir, el conteo físico y la diferencia
     * del cierre anterior se limpian -ya no son válidos si se sigue
     * vendiendo/comprando en efectivo después de reabrir.
     */
    public function reabrir(Caja $caja): JsonResponse
    {
        if ($caja->estaAbierta()) {
            return response()->json(['message' => 'Esta caja ya está abierta.'], 422);
        }

        if (! $caja->esLaUltima()) {
            return response()->json(['message' => 'Ya abriste una caja nueva después de esta -no se puede reabrir.'], 422);
        }

        $caja->update([
            'cierre_en' => null,
            'usuario_cierre_id' => null,
            'conteo_fisico' => null,
            'diferencia' => null,
            'conteo_digital' => null,
            'diferencia_digital' => null,
        ]);

        $caja->load('usuarioApertura');

        return response()->json(['caja' => $this->shapeCajaAbierta($caja)]);
    }

    /**
     * Dos ledgers paralelos, uno por cada "cajón": el físico (efectivo) y
     * el digital (lo que entró por Wompi/transferencia hoy).
     *   esperado efectivo = base inicial + ventas efectivo − gastos
     *     efectivo − compras pagadas en efectivo (del cajón físico).
     *   esperado digital = ventas digitales confirmadas − gastos digitales
     *     − compras pagadas con digital de hoy.
     *   total general = esperado efectivo + esperado digital.
     * Las variantes "_externo" (efectivo_externo/digital_externo) son
     * plata que nunca fue parte de esta caja -no descuentan de ningún lado,
     * tanto en compras como en gastos.
     */
    private function calcularTotales(Caja $caja): array
    {
        $ventasEfectivo = (float) $caja->ventas()->noAnuladas()->where('metodo_pago', 'efectivo')->sum('total');
        $ventasDigital = (float) $caja->ventas()->noAnuladas()->where('metodo_pago', 'digital')->where('estado_pago', 'pagada')->sum('total');
        $comprasEfectivo = (float) $caja->compras()->where('metodo_pago', 'efectivo')->sum('total');
        $comprasDigital = (float) $caja->compras()->where('metodo_pago', 'digital')->sum('total');
        $gastosEfectivo = (float) $caja->gastos()->where('metodo_pago', 'efectivo')->sum('monto');
        $gastosDigital = (float) $caja->gastos()->where('metodo_pago', 'digital')->sum('monto');

        $totalEsperado = (float) $caja->base_inicial + $ventasEfectivo - $gastosEfectivo - $comprasEfectivo;
        $totalEsperadoDigital = $ventasDigital - $gastosDigital - $comprasDigital;

        return [
            'ventasEfectivo' => $ventasEfectivo,
            'ventasDigital' => $ventasDigital,
            'gastosEfectivo' => $gastosEfectivo,
            'gastosDigital' => $gastosDigital,
            'comprasEfectivo' => $comprasEfectivo,
            'comprasDigital' => $comprasDigital,
            'totalEsperado' => $totalEsperado,
            'totalEsperadoDigital' => $totalEsperadoDigital,
            'totalGeneral' => $totalEsperado + $totalEsperadoDigital,
        ];
    }

    private function shapeCajaAbierta(Caja $caja): array
    {
        return array_merge([
            'id' => $caja->id,
            'baseInicial' => (float) $caja->base_inicial,
            'horaApertura' => hora_es($caja->apertura_en),
            'abrioPor' => $caja->usuarioApertura->nombreCompleto(),
        ], $this->calcularTotales($caja));
    }

    private function shapeCierre(Caja $caja): array
    {
        return array_merge([
            'id' => $caja->id,
            'fecha' => $caja->apertura_en->locale('es')->translatedFormat('d M Y'),
            // Para el filtro de mes en el historial: una clave ordenable
            // ("2026-08") y una etiqueta legible ("Agosto 2026").
            'mesKey' => $caja->apertura_en->format('Y-m'),
            'mesLabel' => ucfirst($caja->apertura_en->locale('es')->translatedFormat('F Y')),
            'horaCierre' => hora_es($caja->cierre_en),
            'abrioPor' => $caja->usuarioApertura->nombreCompleto(),
            'baseInicial' => (float) $caja->base_inicial,
            'conteoReal' => (float) $caja->conteo_fisico,
            'diferencia' => (float) $caja->diferencia,
            'conteoDigital' => (float) $caja->conteo_digital,
            'diferenciaDigital' => (float) $caja->diferencia_digital,
        ], $this->calcularTotales($caja));
    }
}
