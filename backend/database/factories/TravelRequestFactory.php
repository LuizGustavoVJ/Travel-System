<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelRequest>
 */
class TravelRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+3 months');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');

        return [
            'user_id' => User::factory(),
            'requester_name' => fake()->name(),
            'destination' => fake()->city() . ', ' . fake()->country(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'requested',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the travel request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory()->admin(),
        ]);
    }

    /**
     * Indicate that the travel request is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by' => User::factory()->admin(),
            'cancelled_reason' => fake()->sentence(),
        ]);
    }
}
