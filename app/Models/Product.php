<?php

namespace App\Models;

use App\Enums\PriceTier;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'sku', 'category', 'price_pesewas', 'wholesale_price_pesewas', 'wholesale_min_quantity', 'stock_quantity', 'description', 'image_path', 'is_active', 'created_by'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    /**
     * At or below this quantity (and above zero) a product is shown as "Low stock".
     */
    public const LOW_STOCK_THRESHOLD = 5;

    /**
     * Filesystem disk and folder that hold product images.
     */
    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'products';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_pesewas' => 'integer',
            'wholesale_price_pesewas' => 'integer',
            'wholesale_min_quantity' => 'integer',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Staff member who added the product.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Current selling price, e.g. "GH₵ 1,200.00". Past sales keep their own price snapshot (ADR-0006).
     */
    public function formattedPrice(): string
    {
        return Money::format($this->price_pesewas);
    }

    public function hasWholesalePrice(): bool
    {
        return $this->wholesale_price_pesewas !== null && $this->wholesale_price_pesewas > 0;
    }

    public function formattedWholesalePrice(): ?string
    {
        return $this->hasWholesalePrice() ? Money::format($this->wholesale_price_pesewas) : null;
    }

    /**
     * The unit price to charge (ADR-0019). Wholesale applies only when the sale is in wholesale mode,
     * the product has a wholesale price and the quantity reaches `wholesale_min_quantity`;
     * otherwise the retail price is used.
     *
     * @return array{pesewas: int, tier: PriceTier}
     */
    public function unitPriceFor(PriceTier $tier, int $quantity): array
    {
        if ($tier === PriceTier::Wholesale && $this->hasWholesalePrice() && $quantity >= max(1, $this->wholesale_min_quantity)) {
            return ['pesewas' => $this->wholesale_price_pesewas, 'tier' => PriceTier::Wholesale];
        }

        return ['pesewas' => $this->price_pesewas, 'tier' => PriceTier::Retail];
    }

    /**
     * Value of the units in stock at the current selling price, in pesewas.
     */
    public function stockValuePesewas(): int
    {
        return $this->stock_quantity * $this->price_pesewas;
    }

    /**
     * "in" (above the low threshold), "low" (1 to LOW_STOCK_THRESHOLD) or "out" (zero).
     */
    public function stockLevel(): string
    {
        return match (true) {
            $this->stock_quantity <= 0 => 'out',
            $this->stock_quantity <= self::LOW_STOCK_THRESHOLD => 'low',
            default => 'in',
        };
    }

    /**
     * Public URL of the product photo, or null when there is none.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk(self::IMAGE_DISK)->url($this->image_path) : null;
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
