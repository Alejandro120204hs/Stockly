<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'gastos';

    protected $fillable = [
        'empresa_id',
        'caja_id',
        'usuario_id',
        'categoria',
        'descripcion',
        'responsable',
        'monto',
        'metodo_pago',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Mismo criterio que Venta::fechaTurno() -a qué "día" pertenece para
     * filtros es el día en que se abrió la caja de ese turno, no su fecha
     * calendario real. Sin caja asociada (pagado fuera de caja), cae de
     * vuelta a su propia fecha.
     */
    public function fechaTurno(): string
    {
        return ($this->caja?->apertura_en ?? $this->fecha)->toDateString();
    }
}
