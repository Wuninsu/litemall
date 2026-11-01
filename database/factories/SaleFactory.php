<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw sale rows for list/query tests. Use SaleService in tests that need real totals, lines and stock changes.
 *
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(10, 500) * 100;

        return [
            'receipt_number' => 'R-'.fake()->unique()->numerify('9#####'),
            'customer_id' => Customer::factory(),
            'sold_by' => User::factory(),
            'subtotal_pesewas' => $total,
            'discount_pesewas' => 0,
            'total_pesewas' => $total,
            'paid_pesewas' => 0,
            'status' => SaleStatus::Unpaid,
            'sold_at' => now(),
        ];
    }

    /**
     * @param  int  $totalPesewas  sale total
     * @param  int  $paidPesewas  amount already paid
     */
    public function amounts(int $totalPesewas, int $paidPesewas = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal_pesewas' => $totalPesewas,
            'total_pesewas' => $totalPesewas,
            'paid_pesewas' => $paidPesewas,
            'status' => SaleStatus::for($totalPesewas, $paidPesewas),
        ]);
    }
}
