<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $table = 'empleados';

    protected $fillable = [
        'empresa_id',
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'cargo',
        'salario',
        'fecha_retiro',
    ];

    protected function casts(): array
    {
        return [
            'salario'      => 'decimal:2',
            'fecha_retiro' => 'date',
        ];
    }

    public function documentosNomina(): HasMany
    {
        return $this->hasMany(NominaDocumento::class);
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function estaActivo(): bool
    {
        return $this->fecha_retiro === null;
    }
}
