<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+233'.fake()->randomElement(['24', '54', '55', '20', '50', '27', '26']).'#######'),
            'email' => fake()->optional(0.5)->safeEmail(),
            'address' => fake()->optional(0.6)->randomElement(['Madina, Accra', 'Adum, Kumasi', 'Tamale Central', 'Osu, Accra', 'Takoradi Market Circle', 'Kasoa']),
            'notes' => null,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
