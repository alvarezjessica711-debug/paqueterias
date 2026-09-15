<?php

namespace Database\Factories;

use App\Models\Residente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Residente>
 */
class ResidenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'telefono' => fake()->numerify('3#########'),
            'correo' => fake()->unique()->safeEmail(),
        ];
    }
}
