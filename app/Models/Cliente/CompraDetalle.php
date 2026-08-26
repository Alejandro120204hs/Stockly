<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDetalle extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'compra_detalle';

    protected $fillable = [
        'empresa_id',
        'compra_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
