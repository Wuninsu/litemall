<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with local development accounts.
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

        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->cashier()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
        ]);
    }
}
