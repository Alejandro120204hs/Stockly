<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoEfectivo extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'pagos_efectivo';

    protected $fillable = [
        'empresa_id',
        'venta_id',
        'monto_recibido',
        'cambio',
    ];

    protected function casts(): array
    {
        return [
            'monto_recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
