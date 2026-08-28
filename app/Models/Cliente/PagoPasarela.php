<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPasarela extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'pagos_pasarela';

    protected $fillable = [
        'empresa_id',
        'venta_id',
        'pasarela',
        'id_transaccion',
        'monto',
        'estado',
        'fecha_confirmacion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_confirmacion' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
