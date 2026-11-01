<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'phone', 'email', 'address', 'notes', 'is_wholesale', 'is_active', 'created_by'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_wholesale' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Staff member who registered the customer.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * What the customer still owes across all sales, in pesewas.
     */
    public function outstandingPesewas(): int
    {
        if (array_key_exists('balance_pesewas', $this->attributes)) {
            return (int) $this->attributes['balance_pesewas'];
        }

        return (int) $this->sales()->sum(DB::raw('total_pesewas - paid_pesewas'));
    }

    /**
     * Adds a `balance_pesewas` column (sum of unpaid amounts) to each customer row.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeWithBalance(Builder $query): void
    {
        $query->addSelect([
            'balance_pesewas' => Sale::query()
                ->selectRaw('coalesce(sum(total_pesewas - paid_pesewas), 0)')
                ->whereColumn('sales.customer_id', 'customers.id'),
        ]);
    }

    /**
     * Phone as people write it locally, e.g. "024 412 3456".
     */
    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->phone);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
