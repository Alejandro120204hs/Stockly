<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprador extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'compradores';

    protected $fillable = [
        'empresa_id',
        'tipo_documento',
        'numero_documento',
        'nombre',
        'correo',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
