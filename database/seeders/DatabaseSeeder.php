<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
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
    }
}
