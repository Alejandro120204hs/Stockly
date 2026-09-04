<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de pagos de suscripción -nace 'pago_recibido' cuando el
 * cliente reporta un pago con comprobante desde /cliente/suscripcion
 * (App\Http\Controllers\Cliente\SuscripcionController), o nace directo
 * 'activado' cuando el admin activa manualmente sin que el cliente haya
 * reportado nada (App\Http\Controllers\Admin\EmpresaController::activar()).
 * El admin aprueba o rechaza un 'pago_recibido' desde
 * App\Http\Controllers\Admin\PagoController.
 *
 * No usa BelongsToEmpresa: no es un dato propio de un tenant, lo
 * administra el Super Admin viendo TODAS las empresas.
 */
class PagoSuscripcion extends Model
{
    use HasFactory;

    protected $table = 'pagos_suscripcion';

    /**
     * Fuente única de precio/duración por plan -antes estaba duplicado
     * en EmpresaController, DashboardController y PagoController.
     */
    public const PLANES = [
        'mensual'    => ['label' => 'Mensual',    'meses' => 1,  'precio' => 150000],
        'trimestral' => ['label' => 'Trimestral', 'meses' => 3,  'precio' => 390000],
        'semestral'  => ['label' => 'Semestral',  'meses' => 6,  'precio' => 805000],
        'anual'      => ['label' => 'Anual',      'meses' => 12, 'precio' => 1140000],
    ];

    protected $fillable = [
        'empresa_id',
        'plan',
        'monto',
        'metodo',
        'estado',
        'comprobante_path',
        'motivo_rechazo',
        'fecha_pago',
        'fecha_activacion',
        'vencimiento_anterior',
        'vencimiento_nuevo',
        'usuario_activador_id',
    ];

    protected function casts(): array
    {
        return [
            'monto'                => 'decimal:2',
            'fecha_pago'           => 'datetime',
            'fecha_activacion'     => 'datetime',
            'vencimiento_anterior' => 'date:Y-m-d',
            'vencimiento_nuevo'    => 'date:Y-m-d',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuarioActivador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_activador_id');
    }

    /**
     * Mismo patrón que Empresa::logoUrl(): asset('storage/...') en vez de
     * Storage::url(), porque este último fija el APP_URL del .env y se
     * rompe si el sitio se ve desde otro host/puerto.
     */
    public function comprobanteUrl(): ?string
    {
        return $this->comprobante_path ? asset('storage/'.$this->comprobante_path) : null;
    }
}
