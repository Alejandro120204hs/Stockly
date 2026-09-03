<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\PagoSuscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagoSuscripcion>
 */
class PagoSuscripcionFactory extends Factory
{
    protected $model = PagoSuscripcion::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'plan' => 'mensual',
            'monto' => 70000,
            'metodo' => 'Nequi',
            'estado' => 'activado',
            'fecha_pago' => now(),
            'fecha_activacion' => now(),
            'vencimiento_anterior' => null,
            'vencimiento_nuevo' => now()->addMonth(),
            'usuario_activador_id' => null,
        ];
    }
}
