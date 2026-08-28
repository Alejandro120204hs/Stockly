<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Un lote = las unidades que entraron juntas en una misma compra, con el
 * costo real que se pagó por ellas. `cantidad_bodega`/`cantidad_vitrina`
 * son lo que queda de ese lote en cada ubicación -bajan a medida que se
 * transfieren o se venden, nunca suben salvo que se anule una venta.
 */
class LoteInventario extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'lotes_inventario';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'compra_detalle_id',
        'costo_unitario',
        'cantidad_bodega',
        'cantidad_vitrina',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'costo_unitario' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function compraDetalle(): BelongsTo
    {
        return $this->belongsTo(CompraDetalle::class, 'compra_detalle_id');
    }

    /**
     * Descuenta `$cantidad` unidades empezando por el lote más antiguo
     * (FIFO) en la columna indicada ('cantidad_bodega' o
     * 'cantidad_vitrina'). `lockForUpdate()` evita que dos ventas
     * simultáneas del mismo producto descuenten del mismo lote dos veces.
     * Devuelve, en orden, de qué lote salió cada porción y a qué costo.
     *
     * @return array<int, array{lote: self, cantidad: int, costoUnitario: float}>
     */
    public static function consumirFifo(int $productoId, string $columna, int $cantidad): array
    {
        $lotes = static::where('producto_id', $productoId)
            ->where($columna, '>', 0)
            ->orderBy('fecha')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $consumo = [];
        $restante = $cantidad;

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }

            $tomado = min($restante, $lote->{$columna});
            $lote->decrement($columna, $tomado);

            $consumo[] = ['lote' => $lote, 'cantidad' => $tomado, 'costoUnitario' => (float) $lote->costo_unitario];
            $restante -= $tomado;
        }

        if ($restante > 0) {
            throw new RuntimeException("Stock insuficiente en lotes para el producto {$productoId}.");
        }

        return $consumo;
    }

    /**
     * Mueve `$cantidad` unidades de bodega a vitrina, lote por lote,
     * empezando por el más antiguo -las unidades siguen perteneciendo al
     * mismo lote (mismo costo), solo cambian de ubicación.
     */
    public static function transferirFifo(int $productoId, int $cantidad): void
    {
        $consumo = static::consumirFifo($productoId, 'cantidad_bodega', $cantidad);

        foreach ($consumo as $item) {
            $item['lote']->increment('cantidad_vitrina', $item['cantidad']);
        }
    }
}
