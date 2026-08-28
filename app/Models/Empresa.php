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
        'logo_path',
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

    /**
     * Null si la empresa no subió logo (siguen mostrando solo el nombre).
     *
     * OJO: no usa Storage::disk('public')->url() -esa URL sale fija del
     * APP_URL del .env sin importar por dónde entró la petición real
     * (ej. queda en "http://localhost" aunque el sitio corra en
     * "http://localhost:8000" o detrás de una subcarpeta de XAMPP), y el
     * logo terminaba pidiéndose a un origen que no existe. asset() sí
     * arma la URL a partir de la petición actual -mismo mecanismo que ya
     * usa asset_v() para el CSS/JS, que nunca ha tenido este problema.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}
