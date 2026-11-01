<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A return of some or all items from a sale (ADR-0020). `type` is `cancellation` when everything left on the
 * sale was returned, otherwise `return`. The credit first reduces what was owed on the sale
 * (`debt_reduction_pesewas`); the rest is refunded to the customer (`refund_pesewas`, `refund_method`).
 */
#[Fillable(['return_number', 'sale_id', 'processed_by', 'type', 'reason', 'credit_pesewas', 'debt_reduction_pesewas', 'refund_pesewas', 'refund_method', 'refund_reference', 'idempotency_key', 'returned_at'])]
class SaleReturn extends Model
{
    use HasUuids;

    public const TYPE_RETURN = 'return';

    public const TYPE_CANCELLATION = 'cancellation';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_pesewas' => 'integer',
            'debt_reduction_pesewas' => 'integer',
            'refund_pesewas' => 'integer',
            'refund_method' => PaymentMethod::class,
            'returned_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return HasMany<SaleReturnLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleReturnLine::class);
    }

    public function isCancellation(): bool
    {
        return $this->type === self::TYPE_CANCELLATION;
    }

    public function money(string $column): string
    {
        return Money::format((int) $this->{$column.'_pesewas'});
    }
}
