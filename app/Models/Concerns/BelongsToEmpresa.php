<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para cualquier modelo del negocio cliente con columna empresa_id: aplica
 * el Global Scope multi-tenant y rellena empresa_id solo con el del usuario
 * autenticado al crear un registro nuevo -para que no haya que repetir
 * "empresa_id" a mano en cada Request::validate()/create() de cada
 * controlador, y sea imposible crear algo para otra empresa por error.
 */
trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::creating(function ($model) {
            if (! $model->empresa_id && auth()->check()) {
                $model->empresa_id = auth()->user()->empresa_id;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
