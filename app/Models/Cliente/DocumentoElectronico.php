<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentoElectronico extends Model
{
    use BelongsToEmpresa;

    protected $table = 'documentos_electronicos';

    protected $fillable = [
        'empresa_id',
        'numero',
        'tipo',
        'comprador_id',
        'cufe',
        'qr_url',
        'valor_total',
        'fecha_emision',
        'anulada_en',
    ];

    protected function casts(): array
    {
        return [
            'valor_total'  => 'decimal:2',
            'fecha_emision' => 'datetime',
            'anulada_en'   => 'datetime',
        ];
    }

    public function estaAnulado(): bool
    {
        return $this->anulada_en !== null;
    }

    public function estado(): string
    {
        return $this->estaAnulado() ? 'anulada' : 'emitida';
    }

    public function scopeNoAnulados(Builder $query): Builder
    {
        return $query->whereNull('anulada_en');
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    public function ventas(): BelongsToMany
    {
        return $this->belongsToMany(Venta::class, 'documento_venta', 'documento_id', 'venta_id');
    }

    /**
     * Genera el número consecutivo de documento para una empresa en el año actual.
     * Formato: {prefijo}-{año}-{secuencia 3 dígitos}
     * El contador es global por empresa y año, no por tipo, para que no haya
     * huecos ni ambigüedades en el historial ("FI-046" tras "DEE-045" es raro).
     */
    public static function generarNumero(int $empresaId, string $tipo): string
    {
        $prefijo = match ($tipo) {
            'factura_individual'  => 'FI',
            'factura_consolidada' => 'FC',
            'dee_pos'             => 'DEE',
        };

        $anio = now()->year;

        $secuencia = static::where('empresa_id', $empresaId)
            ->whereYear('fecha_emision', $anio)
            ->count() + 1;

        return "{$prefijo}-{$anio}-".str_pad($secuencia, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Forma canónica para el JSON que recibe la vista (island + respuesta store).
     */
    public function toResumenArray(): array
    {
        return [
            'id'        => $this->id,
            'numero'    => $this->numero,
            'tipo'      => $this->tipo,
            'comprador' => $this->comprador ? [
                'nombre'  => $this->comprador->nombre,
                'tipoDoc' => $this->comprador->tipo_documento,
                'numDoc'  => $this->comprador->numero_documento,
            ] : null,
            'cufe'       => $this->cufe,
            'valorTotal' => (float) $this->valor_total,
            'ventasIds'  => $this->ventas->pluck('id')->all(),
            'fecha'      => $this->fecha_emision->format('d/m/Y'),
            'estado'     => $this->estado(),
        ];
    }
}
