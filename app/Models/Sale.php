<?php

namespace App\Models;

use App\Enums\PriceTier;
use App\Enums\SaleStatus;
use App\Support\Money;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A completed sale. Amounts are integer pesewas; `paid_pesewas` and `status` are maintained by
 * SaleService / PaymentService only. Sales are never edited or deleted (ADR-0004).
 */
#[Fillable(['receipt_number', 'customer_id', 'sold_by', 'price_tier', 'subtotal_pesewas', 'discount_pesewas', 'discount_reason', 'total_pesewas', 'paid_pesewas', 'status', 'notes', 'idempotency_key', 'sold_at'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_pesewas' => 'integer',
            'discount_pesewas' => 'integer',
            'total_pesewas' => 'integer',
            'paid_pesewas' => 'integer',
            'status' => SaleStatus::class,
            'price_tier' => PriceTier::class,
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function balancePesewas(): int
    {
        return max(0, $this->total_pesewas - $this->paid_pesewas);
    }

    public function money(string $column): string
    {
        return Money::format($column === 'balance' ? $this->balancePesewas() : (int) $this->{$column.'_pesewas'});
    }

    /**
     * Sales with money still owed, oldest first — the order payments are applied in (ADR-0006).
     *
     * @param  Builder<Sale>  $query
     */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereColumn('paid_pesewas', '<', 'total_pesewas')
            ->orderBy('sold_at')
            ->orderBy('receipt_number');
    }
}
