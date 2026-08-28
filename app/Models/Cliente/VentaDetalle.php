<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaDetalle extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'venta_detalle';

    protected $fillable = [
        'empresa_id',
        'venta_id',
        'producto_id',
        'lote_inventario_id',
        'cantidad',
        'precio_unitario_venta',
        'precio_unitario_costo',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario_venta' => 'decimal:2',
            'precio_unitario_costo' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function loteInventario(): BelongsTo
    {
        return $this->belongsTo(LoteInventario::class, 'lote_inventario_id');
    }
}
