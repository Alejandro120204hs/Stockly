<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'factura_validada_id',
        'tipo',
        'total',
        'usuario_id',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function facturaValidada(): BelongsTo
    {
        return $this->belongsTo(FacturaProveedorValidada::class, 'factura_validada_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }
}
