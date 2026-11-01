<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Enums\PriceTier;
use App\Exceptions\Customers\CustomerException;
use App\Exceptions\Sales\SaleException;
use App\Livewire\Forms\CustomerForm;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CustomerService;
use App\Services\ProductService;
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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Full-screen point of sale ("New sale"). Builds the cart from a product grid, takes payment and shows
 * change due. SaleService re-reads prices and stock under a lock — the totals shown here are a preview.
 *
 * Cash: the cashier enters the amount handed over; the payment recorded is capped at the total and the
 * difference is shown as change. Other methods: the amount entered is the amount paid (must not exceed the total).
 */
#[Layout('layouts::pos')]
#[Title('New sale')]
class Create extends Component
{
    /**
     * Products shown in the grid at once.
     */
    public const GRID_LIMIT = 60;

    /**
     * Sent with the sale so a double-submit returns the same sale instead of creating two.
     */
    #[Locked]
    public string $idempotencyKey = '';

    public string $productSearch = '';

    public string $category = '';

    /**
     * Retail or wholesale prices for this sale (PriceTier value).
     */
    #[Locked]
    public string $priceTier = 'retail';

    /**
     * Quantities keyed by product id, in the order products were added.
     *
     * @var array<string, int|string>
     */
    public array $cart = [];

    public string $customerSearch = '';

    /**
     * Null means a walk-in sale (must be paid in full).
     */
    #[Locked]
    public ?string $customerId = null;

    public bool $showCustomerModal = false;

    /**
     * Checkout dialog with payment method and amount received.
     */
    public bool $showPaymentModal = false;

    /**
     * Quick "new customer" form shown in a modal so the cashier never leaves the POS.
     */
    public CustomerForm $customerForm;

    public bool $showDiscount = false;

    public string $discountType = 'amount';

    public string $discountValue = '';

    public string $discountReason = '';

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public string $notes = '';

    /**
     * Set after a successful sale to show the completion screen.
     */
    #[Locked]
    public ?string $completedSaleId = null;

    #[Locked]
    public int $changeDue = 0;

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

        if ($product === null || $product->stock_quantity < 1) {
            return;
        }

        $current = (int) ($this->cart[$product->id] ?? 0);
        $this->cart[$product->id] = min($current + 1, $product->stock_quantity);
        $this->resetErrorBag(['lines', 'cart']);
    }

    /**
     * Enter in the search box: add the product whose SKU matches exactly (barcode scanners type the SKU
     * and press Enter), otherwise the first product shown.
     */
    public function addFirstMatch(): void
    {
        $term = trim($this->productSearch);

        if ($term === '') {
            return;
        }

        $product = Product::query()->active()->where('sku', Str::upper($term))->first()
            ?? $this->productGrid->first(fn (Product $product) => $product->stock_quantity > 0);

        if ($product !== null) {
            $this->addProduct($product->id);
            $this->productSearch = '';
        }
    }

    public function increment(string $productId): void
    {
        $this->addProduct($productId);
    }

    public function decrement(string $productId): void
    {
        $quantity = (int) ($this->cart[$productId] ?? 0) - 1;

        if ($quantity < 1) {
            $this->removeLine($productId);

            return;
        }

        $this->cart[$productId] = $quantity;
    }

    public function removeLine(string $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->resetErrorBag();
    }

    public function selectCustomer(string $customerId): void
    {
        $customer = Customer::query()->active()->find($customerId);

        if ($customer !== null) {
            $this->customerId = $customer->id;
            $this->customerSearch = '';
            $this->resetErrorBag('customer');

            // Wholesale customers switch the sale to wholesale prices (ADR-0019); staff can switch back.
            if ($customer->is_wholesale) {
                $this->priceTier = PriceTier::Wholesale->value;
            }
        }
    }

    public function clearCustomer(): void
    {
        if ($this->customer?->is_wholesale) {
            $this->priceTier = PriceTier::Retail->value;
        }

        $this->customerId = null;
    }

    public function setPriceTier(string $tier): void
    {
        $this->priceTier = (PriceTier::tryFrom($tier) ?? PriceTier::Retail)->value;
    }

    /**
     * Opens the new-customer modal, pre-filling the phone or name from what was typed in the search.
     */
    public function openNewCustomer(): void
    {
        $this->authorize('create', Customer::class);

        $this->customerForm->reset();
        $this->resetErrorBag();

        $typed = trim($this->customerSearch);

        if ($typed !== '') {
            if (PhoneNumber::normalise($typed) !== null) {
                $this->customerForm->phone = $typed;
            } elseif (! preg_match('/\d/', $typed)) {
                $this->customerForm->name = Str::title($typed);
            }
        }

        $this->showCustomerModal = true;
    }

    public function saveNewCustomer(CustomerService $customerService): void
    {
        $this->authorize('create', Customer::class);

        $payload = $this->customerForm->payload();

        try {
            $customer = $customerService->create($payload, auth()->user());
        } catch (CustomerException $exception) {
            $this->addError('customerForm.phone', $exception->getMessage());

            return;
        }

        $this->selectCustomer($customer->id);
        $this->closeNewCustomer();
    }

    public function closeNewCustomer(): void
    {
        $this->showCustomerModal = false;
        $this->customerForm->reset();
        $this->resetErrorBag();
    }

    /**
     * Opens the checkout dialog. The amount received starts at the total so a full payment is one click.
     */
    public function openPayment(): void
    {
        if ($this->cartProducts->isEmpty()) {
            $this->addError('cart', 'Add at least one product to the sale.');

            return;
        }

        $this->resetErrorBag(['payment', 'paymentAmount', 'paymentReference']);

        if (trim($this->paymentAmount) === '') {
            $this->payInFull();
        }

        $this->showPaymentModal = true;
    }

    public function payInFull(): void
    {
        $this->paymentAmount = Money::toInput($this->summary['total']);
    }

    public function setPaymentAmount(int $pesewas): void
    {
        $this->paymentAmount = Money::toInput(max(0, $pesewas));
    }

    public function complete(SaleService $saleService): void
    {
        $this->authorize('create', Sale::class);

        $this->validate($this->rules());

        $summary = $this->summary;

        try {
            $sale = $saleService->create([
                'customer_id' => $this->customerId,
                'lines' => collect($this->cart)->map(fn ($quantity, $productId) => ['product_id' => (string) $productId, 'quantity' => (int) $quantity])->values()->all(),
                'price_tier' => $this->priceTier,
                'discount_type' => trim($this->discountValue) === '' ? null : $this->discountType,
                'discount_value' => $this->discountValueInUnits() ?? 0,
                'discount_reason' => $this->discountReason,
                'payment_pesewas' => $summary['paid'],
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->paymentReference,
                'notes' => $this->notes,
                'idempotency_key' => $this->idempotencyKey,
            ], auth()->user());
        } catch (SaleException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return;
        }

        $this->showPaymentModal = false;
        $this->completedSaleId = $sale->id;
        $this->changeDue = $summary['change'];
    }

    /**
     * Clears everything for the next customer.
     */
    public function newSale(): void
    {
        $this->reset('productSearch', 'category', 'cart', 'customerSearch', 'customerId', 'showDiscount', 'discountType', 'discountValue', 'discountReason', 'paymentAmount', 'paymentReference', 'notes', 'completedSaleId', 'changeDue', 'showPaymentModal');
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->priceTier = PriceTier::Retail->value;
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetErrorBag();
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
                if (trim((string) $value) !== '' && $this->receivedInPesewas() === null) {
                    $fail('Enter the amount in cedis, e.g. 500 or 1,200.50.');
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
     * Products for the grid: active, filtered by search and category.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function productGrid(): Collection
    {
        $term = trim($this->productSearch);

        return Product::query()
            ->active()
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->orderByRaw('stock_quantity = 0')
            ->orderBy('name')
            ->limit(self::GRID_LIMIT)
            ->get();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function categories(): array
    {
        return app(ProductService::class)->categories();
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
     * @return array{items: int, subtotal: int, discount: int, total: int, received: int, paid: int, change: int, balance: int, discountError: string|null}
     */
    #[Computed]
    public function summary(): array
    {
        $subtotal = 0;
        $items = 0;

        foreach ($this->cartProducts as $product) {
            $quantity = max(0, (int) ($this->cart[$product->id] ?? 0));
            $subtotal += $this->unitPrice($product, $quantity)['pesewas'] * $quantity;
            $items += $quantity;
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
        $received = $this->receivedInPesewas() ?? 0;
        $isCash = $this->paymentMethod === PaymentMethod::Cash->value;

        // Cash over the total is handed back as change; other methods are recorded as entered.
        $paid = $isCash ? min($received, $total) : $received;

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'received' => $received,
            'paid' => $paid,
            'change' => $isCash ? max(0, $received - $total) : 0,
            'balance' => max(0, $total - $paid),
            'discountError' => $discountError,
        ];
    }

    /**
     * Exact amount plus the next round cash amounts above the total (e.g. 1,234.50 → 1,234.50, 1,240, 1,250, 1,300, 1,500).
     *
     * @return list<int>
     */
    #[Computed]
    public function quickCashOptions(): array
    {
        $total = $this->summary['total'];

        if ($total <= 0) {
            return [];
        }

        $options = [$total];

        foreach ([1_000, 5_000, 10_000, 20_000, 50_000] as $step) {
            $rounded = (int) (ceil($total / $step) * $step);

            if ($rounded > $total) {
                $options[] = $rounded;
            }
        }

        return array_values(array_slice(array_unique($options), 0, 5));
    }

    #[Computed]
    public function completedSale(): ?Sale
    {
        return $this->completedSaleId ? Sale::query()->with(['customer', 'seller', 'lines'])->find($this->completedSaleId) : null;
    }

    /**
     * Price the POS shows for a product at a quantity — the same rule SaleService applies on save.
     *
     * @return array{pesewas: int, tier: PriceTier}
     */
    public function unitPrice(Product $product, int $quantity = 1): array
    {
        return $product->unitPriceFor(PriceTier::from($this->priceTier), max(1, $quantity));
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

    private function receivedInPesewas(): ?int
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
