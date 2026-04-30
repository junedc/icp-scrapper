<?php

namespace Database\Factories;

use App\Models\DealerOrderSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealerOrderSnapshot>
 */
class DealerOrderSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dealerScope = fake()->slug(2);
        $externalOrderId = fake()->numberBetween(100000, 999999);
        $orderDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'record_key' => sprintf('%s::%s', $dealerScope, $externalOrderId),
            'dealer_scope' => $dealerScope,
            'dealer_id' => fake()->numberBetween(1, 999),
            'dealer_name' => fake()->company(),
            'dealer_user_name' => fake()->name(),
            'dealer_user_email' => fake()->safeEmail(),
            'session_source' => 'impersonation',
            'source_endpoint' => '/api/ordering-portal/my-orders',
            'queried_status' => fake()->randomElement(['Draft', 'Open', 'Quote', 'Completed']),
            'queried_page' => fake()->numberBetween(1, 5),
            'external_order_id' => $externalOrderId,
            'container_id' => fake()->numberBetween(100000, 999999),
            'order_number' => fake()->bothify('ORD-#####'),
            'dealer_reference' => fake()->company(),
            'customer_name' => fake()->name(),
            'has_customer' => true,
            'status' => fake()->randomElement(['Draft', 'Open', 'Completed']),
            'state' => fake()->randomElement(['Order', 'Quote']),
            'payment_status' => fake()->randomElement(['Paid', 'Unpaid', 'Partially Paid']),
            'payment_method' => fake()->randomElement(['Account', 'Card', 'Transfer']),
            'currency' => 'AUD',
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'subtotal_amount' => fake()->randomFloat(2, 90, 4500),
            'discount_amount' => fake()->randomFloat(2, 0, 250),
            'deposit_amount' => fake()->randomFloat(2, 0, 1000),
            'paid_amount' => fake()->randomFloat(2, 0, 4000),
            'balance_amount' => fake()->randomFloat(2, 0, 2500),
            'shipping_amount' => fake()->randomFloat(2, 0, 450),
            'tax_amount' => fake()->randomFloat(2, 0, 500),
            'lead_id' => fake()->optional()->numberBetween(1000, 9999),
            'lead_reference' => fake()->optional()->bothify('LEAD-####'),
            'order_date' => $orderDate,
            'submitted_at_api' => fake()->dateTimeBetween($orderDate, 'now'),
            'created_at_api' => fake()->dateTimeBetween('-8 months', $orderDate),
            'updated_at_api' => fake()->dateTimeBetween($orderDate, 'now'),
            'paid_at_api' => fake()->optional()->dateTimeBetween($orderDate, 'now'),
            'lead_sent_at' => fake()->optional()->dateTimeBetween('-8 months', $orderDate),
            'synced_at' => now(),
        ];
    }
}
