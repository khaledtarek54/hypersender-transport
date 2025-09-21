<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'license_plate' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'make' => fake()->randomElement(['Toyota', 'Ford', 'Chevrolet', 'Nissan', 'Honda', 'BMW', 'Mercedes', 'Volkswagen']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2010, 2024),
            'capacity' => fake()->optional()->numberBetween(4, 50),
            'is_active' => fake()->boolean(85), // 85% chance of being active
        ];
    }
}
