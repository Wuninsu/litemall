<?php

namespace App\Livewire\Customers;

use App\Exceptions\Customers\CustomerException;
use App\Livewire\Forms\CustomerForm;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Customer list with one right-hand panel for creating and editing (list + modal pattern, ADR-0011).
 */
#[Title('Customers')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'active';

    public CustomerForm $form;

    public bool $showModal = false;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', Customer::class);

        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(string $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);

        $this->authorize('update', $customer);

        $this->form->reset();
        $this->form->setCustomer($customer);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(CustomerService $customerService): void
    {
        $customer = $this->form->customer;

        $this->authorize($customer === null ? 'create' : 'update', $customer ?? Customer::class);

        $payload = $this->form->payload();

        try {
            $saved = $customer === null
                ? $customerService->create($payload, auth()->user())
                : $customerService->update($customer, $payload);
        } catch (CustomerException $exception) {
            $this->addError('form.phone', $exception->getMessage());

            return;
        }

        $this->notice = $customer === null ? "{$saved->name} was added." : "{$saved->name} was updated.";
        $this->closeModal();
    }

    public function deactivate(CustomerService $customerService): void
    {
        $customer = $this->form->customer;

        abort_if($customer === null, 404);
        $this->authorize('changeStatus', $customer);

        $customerService->deactivate($customer);

        $this->notice = "{$customer->name} was deactivated.";
        $this->closeModal();
    }

    public function activate(CustomerService $customerService): void
    {
        $customer = $this->form->customer;

        abort_if($customer === null, 404);
        $this->authorize('changeStatus', $customer);

        $customerService->activate($customer);

        $this->notice = "{$customer->name} was reactivated.";
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function render(): View
    {
        $customers = Customer::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $phoneFragment = PhoneNumber::searchFragment($this->search);

                $query->where(function ($query) use ($term, $phoneFragment): void {
                    $query->where('name', 'like', $term)->orWhere('email', 'like', $term);

                    if ($phoneFragment !== null) {
                        $query->orWhere('phone', 'like', '%'.$phoneFragment.'%');
                    }
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.customers.index', ['customers' => $customers]);
    }
}
