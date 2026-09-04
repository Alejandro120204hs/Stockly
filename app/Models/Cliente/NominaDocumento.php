<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento individual de Nómina Electrónica -uno por empleado por pago.
 * A diferencia de DocumentoSoporte, no calcula nada (sin salud, pensión,
 * SMMLV ni retención): el dueño del negocio decide cuánto le paga a cada
 * empleado y este documento solo deja constancia de ese monto.
 */
class NominaDocumento extends Model
{
    use BelongsToEmpresa;

    protected $table = 'nomina_documentos';

    protected $fillable = [
        'empresa_id',
        'empleado_id',
        'caja_id',
        'numero',
        'cune',
        'periodo',
        'monto_pagado',
        'metodo_pago',
        'fecha_pago',
        'fecha_emision',
        'anulada_en',
    ];

    protected function casts(): array
    {
        return [
            'monto_pagado'  => 'decimal:2',
            // Formato explícito -sin esto, Eloquent guarda con hora
            // incluida (el motor de pruebas, SQLite, no la trunca sola
            // como sí hace MySQL con una columna DATE real), rompiendo
            // las comparaciones de rango por fecha en Reportes.
            'fecha_pago'    => 'date:Y-m-d',
            'fecha_emision' => 'datetime',
            'anulada_en'    => 'datetime',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente\Caja::class);
    }

    public function estaAnulado(): bool
    {
        return $this->anulada_en !== null;
    }

    public function estado(): string
    {
        return $this->estaAnulado() ? 'anulada' : 'emitida';
    }

    public static function generarNumero(int $empresaId): string
    {
        $anio = now()->year;

        $secuencia = static::where('empresa_id', $empresaId)
            ->whereYear('fecha_emision', $anio)
            ->count() + 1;

        return 'NE-'.$anio.'-'.str_pad((string) $secuencia, 3, '0', STR_PAD_LEFT);
    }

    public function toResumenArray(): array
    {
        return [
            'id'          => $this->id,
            'numero'      => $this->numero,
            'cune'        => $this->cune,
            'periodo'     => $this->periodo,
            'montoPagado' => (float) $this->monto_pagado,
            'metodoPago'  => $this->metodo_pago,
            'fechaPago'   => $this->fecha_pago->format('d/m/Y'),
            // ISO -para comparar con el <input type="date"> del modal de
            // pago (aviso de "ya le pagaste este mismo día") y para el
            // filtro por mes, sin depender del formato d/m/Y legible.
            'fechaPagoISO' => $this->fecha_pago->toDateString(),
            'mesKey'      => $this->fecha_pago->format('Y-m'),
            'mesLabel'    => ucfirst($this->fecha_pago->locale('es')->translatedFormat('F Y')),
            'fecha'       => $this->fecha_emision->format('d/m/Y'),
            'estado'      => $this->estado(),
            'empleado'    => [
                'id'     => $this->empleado->id,
                'nombre' => $this->empleado->nombreCompleto(),
                'numDoc' => $this->empleado->numero_documento,
            ],
        ];
    }
}
