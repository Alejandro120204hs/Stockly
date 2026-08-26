<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'nombre',
        'precio_costo',
        'precio_venta',
        'unidad_medida',
    ];

    protected function casts(): array
    {
        return [
            'precio_costo' => 'decimal:2',
            'precio_venta' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function inventarioVitrina(): HasOne
    {
        return $this->hasOne(InventarioVitrina::class, 'producto_id');
    }

    public function inventarioBodega(): HasOne
    {
        return $this->hasOne(InventarioBodega::class, 'producto_id');
    }

    public function compraDetalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'producto_id');
    }

    public function movimientosTransferencia(): HasMany
    {
        return $this->hasMany(MovimientoTransferencia::class, 'producto_id');
    }

    public function stockVitrina(): int
    {
        return $this->inventarioVitrina?->stock ?? 0;
    }

    public function stockBodega(): int
    {
        return $this->inventarioBodega?->stock ?? 0;
    }
}
