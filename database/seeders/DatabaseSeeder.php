<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SaleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with local development data.
     *
     * Never run in production: the accounts use the known password "password".
     * Use `php artisan app:create-admin-user` to bootstrap real installations.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DatabaseSeeder creates accounts with known passwords and will not run in production.');

            return;
        }

        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $cashier = User::factory()->cashier()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
        ]);

        Customer::factory()->count(12)->create(['created_by' => $cashier->id]);
        Customer::factory()->count(2)->inactive()->create(['created_by' => $admin->id]);

        foreach ([
            ['Samsung 43-inch Smart TV', 'TV-SAM-43', 'Electronics', 3_450_00, 12],
            ['LG 2-Door Refrigerator', 'FR-LG-2D', 'Appliances', 6_899_00, 4],
            ['Binatone Standing Fan', 'FN-BIN-16', 'Appliances', 450_00, 35],
            ['Nasco 32-inch LED TV', 'TV-NAS-32', 'Electronics', 1_650_00, 0],
            ['Tecno Spark 20', 'PH-TEC-S20', 'Phones', 1_899_50, 20],
            ['3-Seater Fabric Sofa', 'FU-SOF-3', 'Furniture', 4_200_00, 3],
            ['Midea Microwave 20L', 'KT-MID-20', 'Kitchen', 899_99, 15],
            ['Gas Cooker 4-Burner', 'KT-GAS-4B', 'Kitchen', 2_750_00, 8],
        ] as [$name, $sku, $category, $pesewas, $stock]) {
            Product::factory()->priced($pesewas)->create([
                'name' => $name,
                'sku' => $sku,
                'category' => $category,
                'stock_quantity' => $stock,
                'created_by' => $admin->id,
            ]);
        }

        $this->seedSales($cashier);
    }

    /**
     * A few sales through the real services so totals, stock, receipts and allocations are consistent.
     */
    private function seedSales(User $cashier): void
    {
        $sales = app(SaleService::class);
        $payments = app(PaymentService::class);
        $customers = Customer::query()->active()->orderBy('name')->take(3)->get();
        $product = fn (string $sku): string => Product::query()->where('sku', $sku)->value('id');

        $sales->create([
            'lines' => [['product_id' => $product('FN-BIN-16'), 'quantity' => 2]],
            'payment_pesewas' => 900_00,
            'payment_method' => PaymentMethod::Cash,
        ], $cashier);

        $sales->create([
            'customer_id' => $customers[0]->id,
            'lines' => [['product_id' => $product('TV-SAM-43'), 'quantity' => 1], ['product_id' => $product('KT-MID-20'), 'quantity' => 1]],
            'payment_pesewas' => 1_500_00,
            'payment_method' => PaymentMethod::MobileMoney,
            'payment_reference' => 'MOMO-10293847',
        ], $cashier);

        $sales->create([
            'customer_id' => $customers[0]->id,
            'lines' => [['product_id' => $product('PH-TEC-S20'), 'quantity' => 1]],
        ], $cashier);

        $sales->create([
            'customer_id' => $customers[1]->id,
            'lines' => [['product_id' => $product('KT-GAS-4B'), 'quantity' => 1]],
            'discount_type' => 'percent',
            'discount_value' => 500,
            'discount_reason' => 'Returning customer',
            'payment_pesewas' => 1_000_00,
            'payment_method' => PaymentMethod::Cash,
        ], $cashier);

        $payments->receive($customers[0], 2_000_00, PaymentMethod::Cash, $cashier);
    }
}
