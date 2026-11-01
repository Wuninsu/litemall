<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product on a sale. Name, SKU and unit price are copied from the product at the moment of sale
 * so later product changes never alter past sales (ADR-0006).
 */
#[Fillable(['sale_id', 'product_id', 'product_name', 'product_sku', 'unit_price_pesewas', 'quantity', 'line_total_pesewas'])]
class SaleLine extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_pesewas' => 'integer',
            'quantity' => 'integer',
            'line_total_pesewas' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unit_price_pesewas);
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total_pesewas);
    }
}
