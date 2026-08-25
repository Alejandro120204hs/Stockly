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
        if (auth()->check() && auth()->user()->empresa_id) {
            $builder->where($model->getTable().'.empresa_id', auth()->user()->empresa_id);
        }
    }
}
