<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre_negocio' => fake()->unique()->company(),
            'correo_contacto' => fake()->unique()->companyEmail(),
            'estado_suscripcion' => 'activo',
            // Con suscripción vigente por defecto -si no, cualquier test
            // que no le importe el estado de la suscripción se topa con
            // el bloqueo de EnsureSuscripcionActiva (empresa sin
            // fecha_vencimiento = "vencida"). Los tests que sí quieren
            // probar el bloqueo sobrescriben este campo.
            'fecha_vencimiento' => now()->addMonth(),
        ];
    }
}
