<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_see_team_statistics(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ama Mensah']);
        User::factory()->cashier()->count(2)->create();
        User::factory()->cashier()->inactive()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ama')
            ->assertSee('Active staff');

        Livewire::test(Dashboard::class)->assertSet('teamStats', [
            'active' => 3,
            'admins' => 1,
            'cashiers' => 2,
            'inactive' => 1,
        ]);
    }

    public function test_cashiers_do_not_see_team_statistics(): void
    {
        $this->actingAs(User::factory()->cashier()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Active staff')
            ->assertSee('Your account');
    }
}
