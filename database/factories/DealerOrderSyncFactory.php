<?php

namespace Database\Factories;

use App\Models\DealerOrderSync;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealerOrderSync>
 */
class DealerOrderSyncFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dealer_scope' => fake()->slug(2),
            'dealer_id' => fake()->numberBetween(1, 999),
            'dealer_name' => fake()->company(),
            'dealer_user_email' => fake()->safeEmail(),
            'session_source' => fake()->randomElement(['impersonation', 'dealer_login']),
            'status' => fake()->randomElement(['queued', 'running', 'completed']),
            'current_status' => fake()->randomElement(['Open', 'Completed', null]),
            'current_page' => fake()->numberBetween(1, 5),
            'last_page' => fake()->numberBetween(1, 8),
            'total_records' => fake()->numberBetween(0, 500),
            'delay_ms' => 350,
            'create_only' => true,
            'error_message' => null,
            'started_at' => now()->subMinutes(2),
            'finished_at' => fake()->optional()->dateTimeBetween('-1 minute', 'now'),
        ];
    }
}
