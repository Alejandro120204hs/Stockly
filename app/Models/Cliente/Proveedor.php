<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'nit',
        'dv',
        'tipo_persona',
        'regimen_fiscal',
        'telefono',
        'correo',
        'direccion',
        'departamento',
        'ciudad',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }

    public function facturasValidadas(): HasMany
    {
        return $this->hasMany(FacturaProveedorValidada::class, 'proveedor_id');
    }
}
