<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioVitrina extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'inventario_vitrina';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'stock',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
