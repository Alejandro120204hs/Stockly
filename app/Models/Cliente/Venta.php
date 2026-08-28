<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'empresa_id',
        'caja_id',
        'usuario_id',
        'comprador_id',
        'total',
        'metodo_pago',
        'estado_pago',
        'estado_facturacion',
        'anulada_en',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'datetime',
            'anulada_en' => 'datetime',
        ];
    }

    /**
     * Anular NO borra la venta -queda el rastro de que existió, pero se
     * excluye de los totales (Dashboard, Reportes más adelante). El
     * historial de Ventas sí la sigue mostrando, marcada como anulada.
     */
    public function scopeNoAnuladas(Builder $query): Builder
    {
        return $query->whereNull('anulada_en');
    }

    public function estaAnulada(): bool
    {
        return $this->anulada_en !== null;
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Solo está presente si el cliente pidió factura a su nombre al
     * momento de la venta -no implica que el documento DIAN ya se haya
     * generado, eso lo hace el módulo de Facturación más adelante.
     */
    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function pagoEfectivo(): HasOne
    {
        return $this->hasOne(PagoEfectivo::class, 'venta_id');
    }

    public function pagoPasarela(): HasOne
    {
        return $this->hasOne(PagoPasarela::class, 'venta_id');
    }

    /**
     * Ganancia bruta = suma de (precio de venta − precio de costo) de cada
     * línea, usando los precios HISTÓRICOS guardados en venta_detalle -no
     * los precios actuales del producto, que pudieron cambiar desde
     * entonces.
     */
    public function gananciaBruta(): float
    {
        return (float) $this->detalles->sum(
            fn (VentaDetalle $detalle) => $detalle->cantidad * ($detalle->precio_unitario_venta - $detalle->precio_unitario_costo)
        );
    }

    /**
     * Misma forma (camelCase) que ya espera el panel de detalle de venta
     * (Ventas y el Dashboard comparten exactamente este mismo panel, ver
     * cliente/partials/venta-slide-over.blade.php) -se arma una sola vez
     * acá para que las dos páginas queden siempre sincronizadas.
     */
    public function toResumenArray(): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha->toDateString(),
            'hora' => $this->formatearHora(),
            'total' => (float) $this->total,
            'metodo' => $this->metodo_pago,
            'estadoPago' => $this->estado_pago,
            'estadoFacturacion' => $this->estado_facturacion,
            'anulada' => $this->estaAnulada(),
            'comprador' => $this->comprador ? [
                'nombre' => $this->comprador->nombre,
                'tipoDocumento' => $this->comprador->tipo_documento,
                'numeroDocumento' => $this->comprador->numero_documento,
            ] : null,
            'ganancia' => $this->gananciaBruta(),
            'lineas' => $this->detalles->map(fn (VentaDetalle $detalle) => [
                'nombre' => $detalle->producto->nombre,
                'cantidad' => $detalle->cantidad,
                'precio' => (float) $detalle->precio_unitario_venta,
            ])->all(),
        ];
    }

    private function formatearHora(): string
    {
        $hora = hora_es($this->fecha);

        if ($this->fecha->isToday()) {
            return "Hoy, {$hora}";
        }

        if ($this->fecha->isYesterday()) {
            return "Ayer, {$hora}";
        }

        return $this->fecha->locale('es')->translatedFormat('d M').", {$hora}";
    }
}
