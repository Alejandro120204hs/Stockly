<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea /cliente/facturacion/* si la empresa no tiene el módulo de
 * facturación electrónica prendido (Empresa::tiene_facturacion) -las manda
 * al Dashboard con un aviso. El resto de "Administración" (Ventas, Caja,
 * Inventario, etc.) no depende de este interruptor, solo Facturación (ver
 * memoria de dominio: "todas tienen administración, lo único que varía es
 * lo de Factus").
 */
class EnsureFacturacionActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->empresa?->tiene_facturacion) {
            return redirect()->route('cliente.dashboard')->with('status', 'facturacion-bloqueada');
        }

        return $next($request);
    }
}
