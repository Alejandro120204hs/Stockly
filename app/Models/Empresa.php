<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'tiene_facturacion',
    ];

    protected function casts(): array
    {
        return [
            // Formato explícito -sin esto, Eloquent guarda con hora
            // incluida en algunos motores, rompiendo comparaciones de
            // fecha (mismo caso que NominaDocumento::fecha_pago).
            'fecha_vencimiento' => 'date:Y-m-d',
            'tiene_facturacion' => 'boolean',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function pagosSuscripcion(): HasMany
    {
        return $this->hasMany(PagoSuscripcion::class);
    }

    /**
     * Estado real de la licencia, calculado al vuelo contra la fecha de
     * hoy -nunca queda desactualizado porque no depende de ningún job
     * programado. "Suspendido" es la única bandera manual (el admin la
     * prende/apaga sin importar la fecha de vencimiento).
     */
    public function estadoEfectivo(): string
    {
        if ($this->estado_suscripcion === 'suspendido') {
            return 'suspendido';
        }

        if (! $this->fecha_vencimiento || $this->fecha_vencimiento->isPast()) {
            return 'vencido';
        }

        // OJO con el orden: fecha_vencimiento->diffInDays(now()) da NEGATIVO
        // cuando la fecha es futura (Carbon resta "hacia atrás") -desde
        // now() hacia la fecha sí da positivo cuando falta tiempo.
        if (now()->diffInDays($this->fecha_vencimiento) <= 7) {
            return 'por_vencer';
        }

        return 'activo';
    }

    /**
     * Fecha de vencimiento que le quedaría a la empresa si se le activa
     * este plan AHORA MISMO -si todavía le quedaban días pagados
     * (fecha_vencimiento en el futuro), extiende desde ahí para no
     * hacerle perder esos días; si no, extiende desde hoy. Usado tanto
     * por la activación manual del admin (Admin\EmpresaController) como
     * por la aprobación de un pago reportado por el cliente
     * (Admin\PagoController::aprobar()).
     */
    public function calcularNuevoVencimiento(string $plan): Carbon
    {
        $base = ($this->fecha_vencimiento && $this->fecha_vencimiento->isFuture())
            ? $this->fecha_vencimiento
            : now();

        return $base->copy()->addMonths(PagoSuscripcion::PLANES[$plan]['meses']);
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
