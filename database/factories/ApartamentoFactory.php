<?php

namespace Database\Factories;

use App\Models\Apartamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartamento>
 */
class ApartamentoFactory extends Factory
{
    private static int $siguienteNumero = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Keep generated test houses outside the initial A1-F5 catalogue.
            'codigo' => 'F'.(1000 + self::$siguienteNumero++),
        ];
    }
}
