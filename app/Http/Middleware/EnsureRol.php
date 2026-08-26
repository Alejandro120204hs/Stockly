<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a un grupo de rutas si el usuario autenticado no tiene
 * el rol esperado -sin esto, un admin (que no pertenece a ninguna empresa)
 * podía entrar a las rutas de /cliente/* con la misma sesión, y el Global
 * Scope de empresa no tenía nada que filtrar para una cuenta sin empresa_id.
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        if ($request->user()?->rol?->nombre !== $rol) {
            abort(403);
        }

        return $next($request);
    }
}
