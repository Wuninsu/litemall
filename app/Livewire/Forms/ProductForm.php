<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * Input shape and validation for the product panel. The price is typed in cedis ("1,200.50") and
 * converted to integer pesewas here, at the input boundary (ADR-0005). The photo is uploaded to a
 * Livewire temporary file and handed to ProductService, which stores it.
 */
class ProductForm extends Form
{
    /**
     * Largest accepted photo, in kilobytes.
     */
    public const IMAGE_MAX_KB = 2048;

    #[Locked]
    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public string $category = '';

    public string $price = '';

    /**
     * Optional wholesale price in cedis; blank means the product has no wholesale price.
     */
    public string $wholesalePrice = '';

    public string $wholesaleMinQuantity = '1';

    public string $stock = '0';

    public string $description = '';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    public bool $removeImage = false;

    public function setProduct(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->sku = (string) $product->sku;
        $this->category = (string) $product->category;
        $this->price = Money::toInput($product->price_pesewas);
        $this->wholesalePrice = $product->hasWholesalePrice() ? Money::toInput($product->wholesale_price_pesewas) : '';
        $this->wholesaleMinQuantity = (string) max(1, $product->wholesale_min_quantity);
        $this->stock = (string) $product->stock_quantity;
        $this->description = (string) $product->description;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_.\/]+$/', Rule::unique(Product::class, 'sku')->ignore($this->product?->getKey())],
            'category' => ['nullable', 'string', 'max:100'],
            'price' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! Money::isValid((string) $value)) {
                        $fail('Enter the price in cedis, e.g. 1200 or 1,200.50.');

                        return;
                    }

                    if (Money::parse((string) $value) <= 0) {
                        $fail('The price must be more than GH₵ 0.00.');
                    }
                },
            ],
            'wholesalePrice' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (trim((string) $value) === '') {
                        return;
                    }

                    if (! Money::isValid((string) $value) || Money::parse((string) $value) <= 0) {
                        $fail('Enter the wholesale price in cedis, e.g. 950 or 1,050.00.');

                        return;
                    }

                    if (Money::isValid($this->price) && Money::parse((string) $value) > Money::parse($this->price)) {
                        $fail('The wholesale price cannot be more than the retail price.');
                    }
                },
            ],
            'wholesaleMinQuantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::IMAGE_MAX_KB],
            'removeImage' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'sku.regex' => 'The SKU may only contain letters, numbers, dashes, dots, slashes and underscores.',
            'sku.unique' => 'Another product already uses this SKU.',
            'wholesaleMinQuantity.integer' => 'Enter a whole number of units, e.g. 12.',
            'wholesaleMinQuantity.min' => 'The minimum must be at least 1 unit.',
            'stock.integer' => 'Enter a whole number of units, e.g. 12.',
            'stock.min' => 'The quantity in stock cannot be negative.',
            'image.image' => 'The photo must be a JPG, PNG or WebP image.',
            'image.mimes' => 'The photo must be a JPG, PNG or WebP image.',
            'image.max' => 'The photo must be 2 MB or smaller.',
        ];
    }

    /**
     * @return array{name: string, sku: string|null, category: string|null, price_pesewas: int, wholesale_price_pesewas: int|null, wholesale_min_quantity: int, stock_quantity: int, description: string|null}
     */
    public function payload(): array
    {
        $original = $this->sku;
        $this->sku = strtoupper(trim($this->sku));
        $this->stock = trim($this->stock);

        try {
            $validated = $this->validate();
        } finally {
            $this->sku = $original;
        }

        return [
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'category' => $validated['category'] ?? null,
            'price_pesewas' => Money::parse($validated['price']),
            'wholesale_price_pesewas' => trim((string) ($validated['wholesalePrice'] ?? '')) === '' ? null : Money::parse($validated['wholesalePrice']),
            'wholesale_min_quantity' => (int) $validated['wholesaleMinQuantity'],
            'stock_quantity' => (int) $validated['stock'],
            'description' => $validated['description'] ?? null,
        ];
    }
}
