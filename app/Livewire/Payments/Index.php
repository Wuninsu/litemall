<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentMethod;
use App\Exceptions\Payments\PaymentException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\PaymentService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Payments list plus a "Receive payment" panel. Payments are applied to the customer's unpaid sales
 * oldest first by PaymentService; the panel shows that split before saving.
 */
#[Title('Payments')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $period = 'today';

    #[Url]
    public string $method = '';

    public bool $showModal = false;

    public ?string $notice = null;

    #[Locked]
    public string $idempotencyKey = '';

    #[Locked]
    public ?string $customerId = null;

    public string $customerSearch = '';

    public string $amount = '';

    public string $paymentMethod = 'cash';

    public string $reference = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Payment::class);

        if ($customerId = request()->query('customer')) {
            $this->openPanel();
            $this->selectCustomer((string) $customerId);
        } elseif (request()->boolean('receive')) {
            $this->openPanel();
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'period', 'method'], true)) {
            $this->resetPage();
        }
    }

    public function openPanel(): void
    {
        $this->authorize('create', Payment::class);

        $this->reset('customerId', 'customerSearch', 'amount', 'reference', 'notes');
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function selectCustomer(string $customerId): void
    {
        if (Customer::query()->whereKey($customerId)->exists()) {
            $this->customerId = $customerId;
            $this->customerSearch = '';
            $this->resetErrorBag('customerId');
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->amount = '';
    }

    public function payFullBalance(): void
    {
        if ($this->customer) {
            $this->amount = Money::toInput($this->customer->outstandingPesewas());
        }
    }

    public function save(PaymentService $paymentService): void
    {
        $this->authorize('create', Payment::class);

        $this->validate([
            'customerId' => ['required'],
            'amount' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Money::isValid((string) $value) || Money::parse((string) $value) <= 0) {
                    $fail('Enter the amount received in cedis, e.g. 500 or 1,200.50.');
                }
            }],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['customerId.required' => 'Choose the customer who is paying.']);

        try {
            $payment = $paymentService->receive(
                Customer::query()->findOrFail($this->customerId),
                Money::parse($this->amount),
                PaymentMethod::from($this->paymentMethod),
                auth()->user(),
                $this->reference,
                $this->notes,
                $this->idempotencyKey,
            );
        } catch (PaymentException $exception) {
            $this->addError('amount', $exception->getMessage());

            return;
        }

        $this->notice = $payment->formattedAmount().' received from '.$payment->customer->name.'.';
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset('customerId', 'customerSearch', 'amount', 'reference', 'notes');
        $this->resetValidation();
    }

    #[Computed]
    public function customer(): ?Customer
    {
        return $this->customerId ? Customer::query()->withBalance()->find($this->customerId) : null;
    }

    /**
     * Customers who owe money, matching the search.
     *
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customerResults(): Collection
    {
        $term = trim($this->customerSearch);
        $phoneFragment = PhoneNumber::searchFragment($term);

        return Customer::query()
            ->withBalance()
            ->whereHas('sales', fn ($query) => $query->whereColumn('paid_pesewas', '<', 'total_pesewas'))
            ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term, $phoneFragment): void {
                $query->where('name', 'like', "%{$term}%");

                if ($phoneFragment !== null) {
                    $query->orWhere('phone', 'like', "%{$phoneFragment}%");
                }
            }))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * How the entered amount will be split across the customer's unpaid sales.
     *
     * @return list<array{sale: Sale, amount: int}>
     */
    #[Computed]
    public function allocationPreview(): array
    {
        if (! $this->customer || ! Money::isValid($this->amount)) {
            return [];
        }

        $amount = Money::parse($this->amount);

        return $amount > 0 ? app(PaymentService::class)->previewAllocation($this->customer, $amount) : [];
    }

    public function render(): View
    {
        $query = Payment::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term)));
            })
            ->when(PaymentMethod::tryFrom($this->method), fn ($query, PaymentMethod $method) => $query->where('method', $method))
            ->when($this->period === 'today', fn ($query) => $query->where('received_at', '>=', today()))
            ->when($this->period === '7days', fn ($query) => $query->where('received_at', '>=', today()->subDays(6)))
            ->when($this->period === '30days', fn ($query) => $query->where('received_at', '>=', today()->subDays(29)));

        return view('livewire.payments.index', [
            'payments' => (clone $query)->with(['customer', 'receiver', 'allocations.sale'])->latest('received_at')->paginate(20),
            'periodTotal' => (int) (clone $query)->sum('amount_pesewas'),
            'methods' => PaymentMethod::cases(),
        ]);
    }
}
