<?php

namespace Database\Factories;

use App\Models\DealerLeadSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealerLeadSnapshot>
 */
class DealerLeadSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dealerScope = fake()->slug(2);
        $leadId = fake()->numberBetween(1000, 9999);
        $leadDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'record_key' => sprintf('%s::lead-%d', $dealerScope, $leadId),
            'dealer_scope' => $dealerScope,
            'dealer_id' => fake()->numberBetween(1, 999),
            'dealer_name' => fake()->company(),
            'dealer_user_name' => fake()->name(),
            'dealer_user_email' => fake()->safeEmail(),
            'session_source' => 'impersonation',
            'source_endpoint' => '/api/ordering-portal/my-leads',
            'queried_page' => fake()->numberBetween(1, 5),
            'external_lead_id' => $leadId,
            'container_id' => fake()->numberBetween(100000, 999999),
            'lead_reference' => fake()->bothify('LEAD-#####'),
            'status' => fake()->randomElement(['Open', 'Quoted', 'Sent']),
            'state' => fake()->randomElement(['Lead', 'Quote']),
            'currency' => 'AUD',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'quoted_amount' => fake()->randomFloat(2, 100, 5000),
            'order_amount' => fake()->randomFloat(2, 100, 5000),
            'lead_date' => $leadDate,
            'expiry_at' => fake()->dateTimeBetween($leadDate, '+30 days'),
            'created_at_api' => fake()->dateTimeBetween('-8 months', $leadDate),
            'updated_at_api' => fake()->dateTimeBetween($leadDate, 'now'),
            'sent_at_api' => fake()->optional()->dateTimeBetween($leadDate, 'now'),
            'synced_at' => now(),
        ];
    }
}
