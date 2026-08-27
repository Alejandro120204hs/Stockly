<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoTransferencia extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'movimientos_transferencia';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'direccion',
        'cantidad',
        'usuario_id',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
