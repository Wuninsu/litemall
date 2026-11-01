<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw payment rows without allocations. Use PaymentService in tests that need allocations.
 *
 * @extends Factory<Payment>
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
            'customer_id' => Customer::factory(),
            'received_by' => User::factory(),
            'amount_pesewas' => fake()->numberBetween(10, 500) * 100,
            'method' => PaymentMethod::Cash,
            'received_at' => now(),
        ];
    }
}
