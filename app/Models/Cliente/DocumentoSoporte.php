<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Espejo de DocumentoElectronico, pero del lado de los GASTOS: lo genera
 * el negocio para reportar lo que PAGÓ (arriendo, servicios, nómina), no
 * lo que vendió. El "comprador" de Facturación acá es el "beneficiario"
 * -a quién se le pagó.
 */
class DocumentoSoporte extends Model
{
    use BelongsToEmpresa;

    protected $table = 'documentos_soporte';

    protected $fillable = [
        'empresa_id',
        'numero',
        'tipo',
        'beneficiario_nombre',
        'beneficiario_tipo_documento',
        'beneficiario_numero_documento',
        'cufe',
        'valor_total',
        'fecha_emision',
        'anulada_en',
    ];

    protected function casts(): array
    {
        return [
            'valor_total'   => 'decimal:2',
            'fecha_emision' => 'datetime',
            'anulada_en'    => 'datetime',
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

    public function gastos(): BelongsToMany
    {
        return $this->belongsToMany(Gasto::class, 'documento_soporte_gasto', 'documento_id', 'gasto_id');
    }

    /**
     * Mismo patrón que DocumentoElectronico::generarNumero() -consecutivo
     * global por empresa y año, con prefijo según el tipo.
     */
    public static function generarNumero(int $empresaId, string $tipo): string
    {
        $prefijo = $tipo === 'nomina_electronica' ? 'NE' : 'DS';
        $anio = now()->year;

        $secuencia = static::where('empresa_id', $empresaId)
            ->whereYear('fecha_emision', $anio)
            ->count() + 1;

        return "{$prefijo}-{$anio}-".str_pad($secuencia, 3, '0', STR_PAD_LEFT);
    }

    public function toResumenArray(): array
    {
        return [
            'id'            => $this->id,
            'numero'        => $this->numero,
            'tipo'          => $this->tipo,
            'beneficiario'  => [
                'nombre'  => $this->beneficiario_nombre,
                'tipoDoc' => $this->beneficiario_tipo_documento,
                'numDoc'  => $this->beneficiario_numero_documento,
            ],
            'cufe'       => $this->cufe,
            'valorTotal' => (float) $this->valor_total,
            'gastosIds'  => $this->gastos->pluck('id')->all(),
            'fecha'      => $this->fecha_emision->format('d/m/Y'),
            'estado'     => $this->estado(),
        ];
    }
}
