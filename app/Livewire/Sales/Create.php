<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Exceptions\Sales\SaleException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Full-page sale entry ("New sale"). Builds the cart, shows a live total, and hands everything to
 * SaleService, which re-reads prices and stock under a lock — the totals shown here are a preview only.
 */
#[Title('New sale')]
class Create extends Component
{
    /**
     * Sent with the sale so a double-submit returns the same sale instead of creating two.
     */
    #[Locked]
    public string $idempotencyKey = '';

    public string $productSearch = '';

    /**
     * Quantities keyed by product id.
     *
     * @var array<string, int|string>
     */
    public array $cart = [];

    public string $customerSearch = '';

    #[Locked]
    public ?string $customerId = null;

    public string $discountType = 'amount';

    public string $discountValue = '';

    public string $discountReason = '';

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', Sale::class);

        $this->idempotencyKey = (string) Str::uuid();

        if ($customerId = request()->query('customer')) {
            $this->selectCustomer((string) $customerId);
        }
    }

    public function addProduct(string $productId): void
    {
        $product = Product::query()->active()->find($productId);

        if ($product === null) {
            return;
        }

        $current = (int) ($this->cart[$product->id] ?? 0);
        $this->cart[$product->id] = min($current + 1, max(1, $product->stock_quantity));
        $this->productSearch = '';
        $this->resetErrorBag('lines');
    }

    public function removeLine(string $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function selectCustomer(string $customerId): void
    {
        $customer = Customer::query()->active()->find($customerId);

        if ($customer !== null) {
            $this->customerId = $customer->id;
            $this->customerSearch = '';
            $this->resetErrorBag('customer');
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
    }

    public function payInFull(): void
    {
        $this->paymentAmount = Money::toInput($this->summary['total']);
    }

    public function complete(SaleService $saleService): void
    {
        $this->authorize('create', Sale::class);

        $this->validate();

        try {
            $sale = $saleService->create([
                'customer_id' => $this->customerId,
                'lines' => collect($this->cart)->map(fn ($quantity, $productId) => ['product_id' => (string) $productId, 'quantity' => (int) $quantity])->values()->all(),
                'discount_type' => trim($this->discountValue) === '' ? null : $this->discountType,
                'discount_value' => $this->discountValueInUnits() ?? 0,
                'discount_reason' => $this->discountReason,
                'payment_pesewas' => $this->paymentInPesewas() ?? 0,
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->paymentReference,
                'notes' => $this->notes,
                'idempotency_key' => $this->idempotencyKey,
            ], auth()->user());
        } catch (SaleException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return;
        }

        session()->flash('status', "Sale {$sale->receipt_number} saved.");

        $this->redirectRoute('sales.show', ['sale' => $sale], navigate: true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'cart' => ['required', 'array', 'min:1'],
            'cart.*' => ['required', 'integer', 'min:1', 'max:100000'],
            'discountType' => ['required', Rule::in(['amount', 'percent'])],
            'discountValue' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (trim((string) $value) !== '' && $this->discountValueInUnits() === null) {
                    $fail($this->discountType === 'percent' ? 'Enter a percentage between 0 and 100.' : 'Enter the discount in cedis, e.g. 50 or 50.00.');
                }
            }],
            'discountReason' => ['nullable', 'string', 'max:255'],
            'paymentAmount' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (trim((string) $value) !== '' && $this->paymentInPesewas() === null) {
                    $fail('Enter the amount paid in cedis, e.g. 500 or 1,200.50.');
                }
            }],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'cart.required' => 'Add at least one product to the sale.',
            'cart.min' => 'Add at least one product to the sale.',
            'cart.*.integer' => 'Quantities must be whole numbers.',
            'cart.*.min' => 'Quantities must be at least 1.',
        ];
    }

    /**
     * Products in the cart with current price and stock, in the order they were added.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function cartProducts(): Collection
    {
        $ids = array_keys($this->cart);

        return Product::query()->whereKey($ids)->get()->sortBy(fn (Product $product) => array_search($product->id, $ids, true))->values();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function productResults(): Collection
    {
        $term = trim($this->productSearch);

        if ($term === '') {
            return new Collection;
        }

        return Product::query()
            ->active()
            ->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function customer(): ?Customer
    {
        return $this->customerId ? Customer::query()->withBalance()->find($this->customerId) : null;
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customerResults(): Collection
    {
        $term = trim($this->customerSearch);

        if ($term === '') {
            return new Collection;
        }

        $phoneFragment = PhoneNumber::searchFragment($term);

        return Customer::query()
            ->active()
            ->withBalance()
            ->where(function ($query) use ($term, $phoneFragment): void {
                $query->where('name', 'like', "%{$term}%");

                if ($phoneFragment !== null) {
                    $query->orWhere('phone', 'like', "%{$phoneFragment}%");
                }
            })
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    /**
     * Live preview of the totals using current prices. SaleService recalculates on save.
     *
     * @return array{subtotal: int, discount: int, total: int, paid: int, balance: int, discountError: string|null}
     */
    #[Computed]
    public function summary(): array
    {
        $subtotal = 0;

        foreach ($this->cartProducts as $product) {
            $subtotal += $product->price_pesewas * max(0, (int) ($this->cart[$product->id] ?? 0));
        }

        $discount = 0;
        $discountError = null;
        $units = $this->discountValueInUnits();

        if ($units !== null && $units > 0) {
            try {
                $discount = app(SaleService::class)->discountFor($subtotal, $this->discountType, $units, auth()->user());
            } catch (SaleException $exception) {
                $discountError = $exception->getMessage();
            }
        }

        $total = $subtotal - $discount;
        $paid = min($this->paymentInPesewas() ?? 0, $total);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'paid' => $paid,
            'balance' => $total - $paid,
            'discountError' => $discountError,
        ];
    }

    private function discountValueInUnits(): ?int
    {
        $value = trim($this->discountValue);

        if ($value === '') {
            return 0;
        }

        try {
            return $this->discountType === 'percent' ? Money::parsePercent($value) : Money::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function paymentInPesewas(): ?int
    {
        $value = trim($this->paymentAmount);

        if ($value === '') {
            return 0;
        }

        return Money::isValid($value) ? Money::parse($value) : null;
    }

    public function render(): View
    {
        return view('livewire.sales.create', [
            'methods' => PaymentMethod::cases(),
            'cashierLimit' => (float) config('sales.cashier_max_discount_percent'),
        ]);
    }
}
