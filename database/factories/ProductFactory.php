<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(fake()->numberBetween(2, 3), true)),
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'category' => fake()->randomElement(['Electronics', 'Appliances', 'Furniture', 'Phones', 'Kitchen']),
            'price_pesewas' => fake()->numberBetween(5, 5000) * 100,
            'stock_quantity' => fake()->numberBetween(10, 60),
            'description' => null,
            'is_active' => true,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * @param  int  $pesewas  wholesale price in pesewas
     * @param  int  $minimumQuantity  units needed before the wholesale price applies
     */
    public function wholesale(int $pesewas, int $minimumQuantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'wholesale_price_pesewas' => $pesewas,
            'wholesale_min_quantity' => $minimumQuantity,
        ]);
    }

    /**
     * @param  int  $pesewas  price in pesewas, e.g. 120050 for GH₵ 1,200.50
     */
    public function priced(int $pesewas): static
    {
        return $this->state(fn (array $attributes) => [
            'price_pesewas' => $pesewas,
        ]);
    }
}
