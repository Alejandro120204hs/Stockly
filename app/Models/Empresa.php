<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre_negocio',
        'tipo_negocio',
        'nit',
        'dv',
        'tipo_persona',
        'regimen_fiscal',
        'correo_contacto',
        'telefono_contacto',
        'direccion',
        'departamento',
        'ciudad',
        'estado_suscripcion',
        'fecha_vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }
}
