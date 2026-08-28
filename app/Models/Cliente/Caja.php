<?php

namespace App\Models\Cliente;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Caja extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'empresa_id',
        'usuario_apertura_id',
        'usuario_cierre_id',
        'base_inicial',
        'apertura_en',
        'cierre_en',
        'conteo_fisico',
        'diferencia',
        'conteo_digital',
        'diferencia_digital',
    ];

    protected function casts(): array
    {
        return [
            'base_inicial' => 'decimal:2',
            'conteo_fisico' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'conteo_digital' => 'decimal:2',
            'diferencia_digital' => 'decimal:2',
            'apertura_en' => 'datetime',
            'cierre_en' => 'datetime',
        ];
    }

    /**
     * Una caja es una SESIÓN (abrir -> cerrar), no un día calendario -puede
     * cruzar la medianoche si el negocio cierra tarde. Mientras esté
     * abierta, todas las ventas/compras en efectivo se le van sumando a
     * través de caja_id.
     */
    public function estaAbierta(): bool
    {
        return $this->cierre_en === null;
    }

    /**
     * Solo la caja MÁS RECIENTE de la empresa se puede reabrir si se cerró
     * por error -en cuanto se abre una caja nueva, la anterior queda
     * bloqueada para siempre como historial real.
     */
    public function esLaUltima(): bool
    {
        return $this->id === static::max('id');
    }

    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_apertura_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cierre_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'caja_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'caja_id');
    }

    /**
     * "Hoy" para Dashboard/Ventas/Gastos no es medianoche real -es desde
     * que abriste tu turno actual. Si el negocio cierra pasada la
     * medianoche, esa venta de la 1am sigue contando como parte del mismo
     * "hoy" mientras el turno siga abierto (igual que ya funciona el
     * historial de Caja, que agrupa por apertura, no por calendario). Sin
     * caja abierta, no hay turno al cual referenciar -se cae de vuelta a
     * la medianoche real de siempre.
     */
    public static function inicioDeHoy(): Carbon
    {
        $abierta = static::whereNull('cierre_en')->first();

        return $abierta ? $abierta->apertura_en->copy() : now()->startOfDay();
    }
}
