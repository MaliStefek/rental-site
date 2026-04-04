<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', 'now');
        $end = (clone $start)->modify('+'.rand(1, 14).' days');

        $subtotal = $this->faker->numberBetween(1000, 20000);
        $late = $this->faker->numberBetween(0, 2000);
        $damage = $this->faker->numberBetween(0, 3000);
        $total = $subtotal + $late + $damage;

        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement([
                'active', 'confirmed', 'cancelled', 'draft', 'overdue', 'returned',
            ]),
            'start_at' => $start,
            'end_at' => $end,
            'returned_at' => null,
            'subtotal_cents' => $subtotal,
            'late_fee_cents' => $late,
            'damage_fee_cents' => $damage,
            'total_cents' => $total,
            'paid_cents' => $this->faker->numberBetween(0, $total),
            'payment_status' => $this->faker->randomElement([
                'paid', 'unpaid', 'partial', 'refunded',
            ]),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
