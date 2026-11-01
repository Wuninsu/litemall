<?php

namespace App\Livewire\Forms;

use App\Models\Customer;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Input shape and validation for the customer panel. Phone numbers are normalised to +233… before the
 * uniqueness check so "024 412 3456" and "+233244123456" are treated as the same number.
 */
class CustomerForm extends Form
{
    #[Locked]
    public ?Customer $customer = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $notes = '';

    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->phone = $customer->formattedPhone();
        $this->email = (string) $customer->email;
        $this->address = (string) $customer->address;
        $this->notes = (string) $customer->notes;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (PhoneNumber::normalise((string) $value) === null) {
                        $fail('Enter a valid Ghana phone number, e.g. 024 412 3456.');
                    }
                },
                Rule::unique(Customer::class, 'phone')->ignore($this->customer?->getKey()),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.unique' => 'Another customer already uses this phone number.',
        ];
    }

    /**
     * @return array{name: string, phone: string, email: string|null, address: string|null, notes: string|null}
     */
    public function payload(): array
    {
        $original = $this->phone;
        $this->phone = PhoneNumber::normalise($this->phone) ?? $this->phone;

        try {
            $validated = $this->validate();
        } finally {
            $this->phone = $original;
        }

        return [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
