<?php

namespace Tests\Feature\Services;

use App\Exceptions\Customers\CustomerException;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CustomerService::class);
    }

    public function test_create_normalises_input_and_records_who_added_the_customer(): void
    {
        $cashier = User::factory()->cashier()->create();

        $customer = $this->service->create([
            'name' => '  Kwame   Asante ',
            'phone' => '024 412 3456',
            'email' => ' Kwame@Example.COM ',
            'address' => '  Madina, Accra ',
            'notes' => '   ',
        ], $cashier);

        $this->assertTrue(Str::isUuid($customer->id));
        $this->assertSame('Kwame Asante', $customer->name);
        $this->assertSame('+233244123456', $customer->phone);
        $this->assertSame('kwame@example.com', $customer->email);
        $this->assertSame('Madina, Accra', $customer->address);
        $this->assertNull($customer->notes);
        $this->assertTrue($customer->is_active);
        $this->assertTrue($customer->creator->is($cashier));
    }

    public function test_invalid_phone_numbers_are_rejected(): void
    {
        $this->expectExceptionObject(CustomerException::invalidPhone());

        $this->service->create(['name' => 'Ama', 'phone' => '12345'], User::factory()->create());
    }

    public function test_the_same_number_written_differently_is_a_duplicate(): void
    {
        $actor = User::factory()->create();
        $this->service->create(['name' => 'Ama', 'phone' => '0244123456'], $actor);

        $this->expectExceptionObject(CustomerException::phoneTaken());

        $this->service->create(['name' => 'Ama Again', 'phone' => '+233 24 412 3456'], $actor);
    }

    public function test_update_keeps_the_original_creator(): void
    {
        $creator = User::factory()->create();
        $customer = Customer::factory()->create(['created_by' => $creator->id]);

        $this->service->update($customer, ['name' => 'New Name', 'phone' => '0554000111', 'email' => '']);

        $customer->refresh();
        $this->assertSame('New Name', $customer->name);
        $this->assertSame('+233554000111', $customer->phone);
        $this->assertNull($customer->email);
        $this->assertSame($creator->id, $customer->created_by);
    }

    public function test_a_customer_can_keep_their_own_number_on_update(): void
    {
        $customer = Customer::factory()->create(['phone' => '+233244123456']);

        $this->service->update($customer, ['name' => $customer->name, 'phone' => '024 412 3456']);

        $this->assertSame('+233244123456', $customer->fresh()->phone);
    }

    public function test_deactivate_and_activate(): void
    {
        $customer = Customer::factory()->create();

        $this->service->deactivate($customer);
        $this->assertFalse($customer->fresh()->is_active);

        $this->service->activate($customer);
        $this->assertTrue($customer->fresh()->is_active);
    }
}
