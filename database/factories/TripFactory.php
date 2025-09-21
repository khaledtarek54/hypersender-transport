<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+1 month');
        $startTimeCarbon = \Carbon\Carbon::parse($startTime);
        $endTime = $startTimeCarbon->copy()->addHours(fake()->numberBetween(1, 8));
        
        return [
            'company_id' => \App\Models\Company::factory(),
            'driver_id' => \App\Models\Driver::factory(),
            'vehicle_id' => \App\Models\Vehicle::factory(),
            'origin' => fake()->city(),
            'destination' => fake()->city(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'distance' => fake()->optional()->randomFloat(2, 10, 500),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
