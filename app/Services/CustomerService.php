<?php

namespace App\Services;

use App\Exceptions\Customers\CustomerException;
use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Registers and maintains customers. Customers are never deleted (their sales and payments
 * must stay traceable); they are deactivated instead.
 */
class CustomerService
{
    /**
     * @param  array{name: string, phone: string, email?: string|null, address?: string|null, notes?: string|null, is_wholesale?: bool}  $data
     *
     * @throws CustomerException
     */
    public function create(array $data, User $actor): Customer
    {
        return $this->persist(new Customer([
            'is_active' => true,
            'created_by' => $actor->getKey(),
        ]), $data);
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, address?: string|null, notes?: string|null, is_wholesale?: bool}  $data
     *
     * @throws CustomerException
     */
    public function update(Customer $customer, array $data): Customer
    {
        return $this->persist($customer, $data);
    }

    public function deactivate(Customer $customer): void
    {
        $customer->forceFill(['is_active' => false])->save();
    }

    public function activate(Customer $customer): void
    {
        $customer->forceFill(['is_active' => true])->save();
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, address?: string|null, notes?: string|null, is_wholesale?: bool}  $data
     *
     * @throws CustomerException
     */
    private function persist(Customer $customer, array $data): Customer
    {
        $phone = PhoneNumber::normalise($data['phone']);

        if ($phone === null) {
            throw CustomerException::invalidPhone();
        }

        $customer->fill([
            'name' => Str::squish($data['name']),
            'phone' => $phone,
            'email' => $this->blankToNull($data['email'] ?? null, lower: true),
            'address' => $this->blankToNull($data['address'] ?? null),
            'notes' => $this->blankToNull($data['notes'] ?? null),
            'is_wholesale' => (bool) ($data['is_wholesale'] ?? $customer->is_wholesale ?? false),
        ]);

        try {
            $customer->save();
        } catch (UniqueConstraintViolationException) {
            throw CustomerException::phoneTaken();
        }

        return $customer;
    }

    private function blankToNull(?string $value, bool $lower = false): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $lower ? Str::lower($value) : $value;
    }
}
