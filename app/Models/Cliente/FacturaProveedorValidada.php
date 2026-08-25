<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaProveedorValidada extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'facturas_proveedor_validadas';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'cufe',
        'fecha_emision',
        'valor_total',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
