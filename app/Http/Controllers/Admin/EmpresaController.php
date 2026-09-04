<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::orderBy('nombre_negocio')
            ->get()
            ->map(fn (Empresa $e) => $this->shapeEmpresa($e));

        return view('admin.empresas', [
            'empresas' => $empresas,
        ]);
    }

    /**
     * El pago llega por fuera del sistema (Nequi, transferencia...) -este
     * endpoint es lo que el admin usa para dejar constancia de que ya lo
     * confirmó y activar el plan correspondiente.
     *
     * Si a la empresa todavía le quedaban días pagados (fecha_vencimiento
     * en el futuro), la renovación se suma desde ESA fecha, no desde hoy
     * -para no hacerle perder los días que ya había pagado si renueva con
     * anticipación. Si ya venció (o nunca se había activado), se suma
     * desde hoy.
     */
    public function activar(Request $request, Empresa $empresa): JsonResponse
    {
        $validated = $request->validate([
            'plan'   => ['required', Rule::in(array_keys(PagoSuscripcion::PLANES))],
            'monto'  => ['nullable', 'numeric', 'min:0'],
            'metodo' => ['nullable', 'string', 'max:100'],
        ]);

        $vencimientoAnterior = $empresa->fecha_vencimiento;
        $vencimientoNuevo = $empresa->calcularNuevoVencimiento($validated['plan']);

        DB::transaction(function () use ($empresa, $validated, $vencimientoAnterior, $vencimientoNuevo) {
            $empresa->update([
                'estado_suscripcion' => 'activo',
                'fecha_vencimiento'  => $vencimientoNuevo,
            ]);

            PagoSuscripcion::create([
                'empresa_id'            => $empresa->id,
                'plan'                  => $validated['plan'],
                'monto'                 => $validated['monto'] ?? null,
                'metodo'                => $validated['metodo'] ?? null,
                'estado'                => 'activado',
                'fecha_pago'            => now(),
                'fecha_activacion'      => now(),
                'vencimiento_anterior'  => $vencimientoAnterior,
                'vencimiento_nuevo'     => $vencimientoNuevo,
                'usuario_activador_id'  => auth()->id(),
            ]);
        });

        return response()->json(['empresa' => $this->shapeEmpresa($empresa->fresh())]);
    }

    /**
     * Suspensión manual -no toca fecha_vencimiento, así que si el admin
     * la reactiva después (con un nuevo pago), los días que le quedaban
     * siguen contando (ver activar()).
     */
    public function suspender(Empresa $empresa): JsonResponse
    {
        $empresa->update(['estado_suscripcion' => 'suspendido']);

        return response()->json(['empresa' => $this->shapeEmpresa($empresa->fresh())]);
    }

    /**
     * Prende/apaga Facturación electrónica (Factus) para la empresa -el
     * único módulo opcional que existe. Nómina va incluida siempre dentro
     * de Administración (toda empresa la tiene, con o sin Factus; la
     * diferencia real es si el soporte de nómina se puede emitir a la DIAN
     * o se queda como registro interno). Por ahora solo se guarda; no
     * bloquea nada del lado cliente todavía (fase aparte, pendiente).
     */
    public function modulos(Request $request, Empresa $empresa): JsonResponse
    {
        $validated = $request->validate([
            'tiene_facturacion' => ['required', 'boolean'],
        ]);

        $empresa->update($validated);

        return response()->json(['empresa' => $this->shapeEmpresa($empresa->fresh())]);
    }

    private function shapeEmpresa(Empresa $empresa): array
    {
        return [
            'id'            => $empresa->id,
            'nombre'        => $empresa->nombre_negocio,
            'tipo'          => $empresa->tipo_negocio,
            'nit'           => $empresa->nit,
            'dv'            => $empresa->dv,
            'tipoPersona'   => $empresa->tipo_persona === 'juridica' ? 'Jurídica' : 'Natural',
            'regimen'       => $empresa->regimen_fiscal,
            'correo'        => $empresa->correo_contacto,
            'telefono'      => $empresa->telefonoContacto(),
            'direccion'     => $empresa->direccion,
            'departamento'  => $empresa->departamento,
            'ciudad'        => $empresa->ciudad,
            'estado'            => $empresa->estadoEfectivo(),
            'vencimiento'       => $empresa->fecha_vencimiento?->locale('es')->translatedFormat('d M Y') ?? 'Sin activar',
            'vencimientoRaw'    => $empresa->fecha_vencimiento?->toDateString(),
            'tieneFacturacion'  => $empresa->tiene_facturacion,
        ];
    }
}
