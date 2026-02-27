<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Rental;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'amount_cents' => $this->faker->numberBetween(1000, 10000),
            'payment_method' => $this->faker->randomElement([
                'card','transfer','cash'
            ]),
            'transaction_reference' => strtoupper(Str::random(10)),
            'paid_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
