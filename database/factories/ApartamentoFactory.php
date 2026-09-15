<?php

namespace Database\Factories;

use App\Models\Apartamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartamento>
 */
class ApartamentoFactory extends Factory
{
    private static int $siguienteNumero = 1000;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'torre' => fake()->randomElement(['Torre 1', 'Torre 2']),
            // Avoid random collisions with the torre + numero database constraint.
            'numero' => (string) self::$siguienteNumero++,
        ];
    }
}
