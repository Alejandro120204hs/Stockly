<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra automáticamente cualquier consulta a un modelo del negocio cliente
 * por la empresa del usuario autenticado -así un negocio nunca puede ver
 * datos de otro, sin depender de que cada controlador se acuerde de agregar
 * el ->where('empresa_id', ...) a mano.
 */
class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $empresaId = auth()->user()->empresa_id;

        if ($empresaId) {
            $builder->where($model->getTable().'.empresa_id', $empresaId);

            return;
        }

        // Un usuario autenticado sin empresa (los admins de DevSec, que no
        // pertenecen a un negocio cliente) NO debe ver nada de estas tablas
        // -si no se fuerza esto, saltarse el where de arriba dejaría la
        // consulta sin filtrar y expondría el inventario de TODAS las
        // empresas a cualquier cuenta admin.
        $builder->whereRaw('1 = 0');
    }
}
