<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea TODAS las rutas /cliente/* si la suscripción de la empresa está
 * vencida o suspendida -las manda a /cliente/suscripcion, la única ruta
 * cliente que queda FUERA de este middleware (si no, nadie bloqueado
 * podría nunca llegar a reportar un pago). Ver
 * App\Http\Controllers\Cliente\SuscripcionController.
 */
class EnsureSuscripcionActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresa = $request->user()?->empresa;

        if (! $empresa || in_array($empresa->estadoEfectivo(), ['vencido', 'suspendido'], true)) {
            return redirect()->route('cliente.suscripcion');
        }

        return $next($request);
    }
}
