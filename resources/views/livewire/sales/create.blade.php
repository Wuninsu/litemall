<div class="grid items-start gap-6 xl:grid-cols-[1fr_400px]">
    {{-- Left: products and cart --}}
    <div class="grid gap-5">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
            <label for="product-search" class="text-[13px] font-semibold text-slate-700">Add products</label>
            <div class="relative mt-1.5">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.250ms="productSearch" id="product-search" type="search" autocomplete="off" autofocus
                       placeholder="Search product name or SKU"
                       class="h-11 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-10 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            </div>

            @if ($productSearch !== '')
                <ul class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                    @forelse ($this->productResults as $result)
                        <li wire:key="result-{{ $result->id }}">
                            <button type="button" wire:click="addProduct('{{ $result->id }}')" @disabled($result->stock_quantity < 1)
                                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-teal-50/60 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent">
                                <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-slate-300">
                                    @if ($result->imageUrl())
                                        <img src="{{ $result->imageUrl() }}" alt="" class="size-full object-cover">
                                    @else
                                        <x-icon name="box" class="size-4" />
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $result->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $result->sku ?? 'No SKU' }} · {{ $result->stock_quantity }} in stock</span>
                                </span>
                                <span class="text-sm font-bold tabular-nums">{{ $result->formattedPrice() }}</span>
                                <x-icon name="plus" class="size-4 text-teal-700" />
                            </button>
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center text-sm text-slate-500">No active products match “{{ $productSearch }}”.</li>
                    @endforelse
                </ul>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="font-bold">Items</h2>
                <span class="text-[13px] text-slate-500">{{ count($cart) }} {{ Str::plural('product', count($cart)) }}</span>
            </div>

            @error('lines') <p class="border-b border-red-100 bg-red-50 px-5 py-2.5 text-[13px] text-red-700">{{ $message }}</p> @enderror
            @error('cart') <p class="border-b border-red-100 bg-red-50 px-5 py-2.5 text-[13px] text-red-700">{{ $message }}</p> @enderror

            @if ($this->cartProducts->isEmpty())
                <div class="grid justify-items-center gap-2 px-5 py-12 text-center text-slate-500">
                    <x-icon name="cart" class="size-8 text-slate-300" />
                    <p class="text-sm">Search above and add products to start the sale.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11.5px] font-bold tracking-wider text-slate-400 uppercase">
                                <th class="px-5 py-2.5">Product</th>
                                <th class="px-3 py-2.5 text-right">Price</th>
                                <th class="px-3 py-2.5">Qty</th>
                                <th class="px-3 py-2.5 text-right">Total</th>
                                <th class="px-3 py-2.5"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->cartProducts as $product)
                                @php($quantity = max(0, (int) ($cart[$product->id] ?? 0)))
                                <tr wire:key="line-{{ $product->id }}">
                                    <td class="px-5 py-3">
                                        <p class="font-semibold">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $product->stock_quantity }} in stock</p>
                                        @error('cart.'.$product->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap tabular-nums">{{ $product->formattedPrice() }}</td>
                                    <td class="px-3 py-3">
                                        <input wire:model.live.debounce.300ms="cart.{{ $product->id }}" type="number" min="1" max="{{ max(1, $product->stock_quantity) }}" step="1"
                                               aria-label="Quantity of {{ $product->name }}"
                                               class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-sm tabular-nums focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold whitespace-nowrap tabular-nums">{{ App\Support\Money::format($product->price_pesewas * $quantity) }}</td>
                                    <td class="px-3 py-3 text-right">
                                        <x-button variant="subtle" size="icon" icon="x" wire:click="removeLine('{{ $product->id }}')" aria-label="Remove {{ $product->name }}" title="Remove" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Right: customer, discount, payment, totals --}}
    <form wire:submit="complete" class="grid gap-5 xl:sticky xl:top-20">
        {{-- Customer --}}
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5">
            <h2 class="mb-3 font-bold">Customer</h2>

            @if ($this->customer)
                <div class="flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50/60 p-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-teal-700 text-xs font-extrabold text-white"><x-icon name="people" class="size-4" /></span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-bold">{{ $this->customer->name }}</p>
                        <p class="text-[13px] text-slate-600">{{ $this->customer->formattedPhone() }}</p>
                        @if ($this->customer->outstandingPesewas() > 0)
                            <p class="mt-1 text-[13px] font-semibold text-amber-700">Already owes {{ App\Support\Money::format($this->customer->outstandingPesewas()) }}</p>
                        @endif
                    </div>
                    <x-button variant="subtle" size="sm" wire:click="clearCustomer" :loading="false">Change</x-button>
                </div>
            @else
                <div class="relative">
                    <input wire:model.live.debounce.250ms="customerSearch" id="customer-search" type="search" autocomplete="off" placeholder="Search name or phone (optional)"
                           aria-label="Search customers"
                           class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                </div>
                @if ($customerSearch !== '')
                    <ul class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                        @forelse ($this->customerResults as $result)
                            <li wire:key="customer-{{ $result->id }}">
                                <button type="button" wire:click="selectCustomer('{{ $result->id }}')" class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-teal-50/60">
                                    <span>
                                        <span class="block font-semibold">{{ $result->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $result->formattedPhone() }}</span>
                                    </span>
                                    @if ($result->outstandingPesewas() > 0)
                                        <span class="text-xs font-semibold text-amber-700">owes {{ App\Support\Money::format($result->outstandingPesewas()) }}</span>
                                    @endif
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-3 text-center text-sm text-slate-500">No active customer found. <a href="{{ route('customers.index') }}" wire:navigate class="font-semibold text-teal-700 underline">Add customer</a></li>
                        @endforelse
                    </ul>
                @endif
                <p class="mt-2 text-[12.5px] text-slate-500">Leave empty for a walk-in sale paid in full.</p>
            @endif
            @error('customer') <p class="mt-2 text-[13px] text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Discount --}}
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold">Discount</h2>
                <div class="inline-flex rounded-lg border border-slate-200 p-0.5 text-[13px] font-semibold" role="radiogroup" aria-label="Discount type">
                    <label class="cursor-pointer rounded-md px-2.5 py-1 has-checked:bg-teal-700 has-checked:text-white">
                        <input type="radio" wire:model.live="discountType" value="amount" class="sr-only"> GH₵
                    </label>
                    <label class="cursor-pointer rounded-md px-2.5 py-1 has-checked:bg-teal-700 has-checked:text-white">
                        <input type="radio" wire:model.live="discountType" value="percent" class="sr-only"> %
                    </label>
                </div>
            </div>
            <div class="grid gap-3">
                <x-input wire:model.live.debounce.400ms="discountValue" id="discount-value" :label="$discountType === 'percent' ? 'Discount (%)' : 'Discount (GH₵)'"
                         :prefix="$discountType === 'percent' ? null : 'GH₵'" placeholder="0" inputmode="decimal" autocomplete="off" error="discountValue" />
                @if ($this->summary['discountError'])
                    <p class="-mt-1 text-[13px] text-red-600">{{ $this->summary['discountError'] }}</p>
                @endif
                @error('discount') <p class="-mt-1 text-[13px] text-red-600">{{ $message }}</p> @enderror
                @if (! auth()->user()->isAdmin())
                    <p class="-mt-1 text-[12.5px] text-slate-500">Cashiers can give up to {{ rtrim(rtrim(number_format($cashierLimit, 2), '0'), '.') }}%.</p>
                @endif
                <x-input wire:model="discountReason" id="discount-reason" label="Reason (optional)" placeholder="e.g. Loyal customer" />
            </div>
        </section>

        {{-- Payment now --}}
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold">Payment now</h2>
                <x-button variant="secondary" size="sm" wire:click="payInFull" :loading="false">Pay in full</x-button>
            </div>
            <div class="grid gap-3">
                <x-input wire:model.live.debounce.400ms="paymentAmount" id="payment-amount" label="Amount paid" prefix="GH₵" placeholder="0.00" inputmode="decimal" autocomplete="off" />
                @error('payment') <p class="-mt-1 text-[13px] text-red-600">{{ $message }}</p> @enderror
                <div class="grid gap-1.5">
                    <label for="payment-method" class="text-[13px] font-semibold text-slate-700">Method</label>
                    <select wire:model.live="paymentMethod" id="payment-method" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if (App\Enums\PaymentMethod::tryFrom($paymentMethod)?->expectsReference())
                    <x-input wire:model="paymentReference" id="payment-reference" label="Transaction reference (optional)" placeholder="e.g. MoMo transaction ID" />
                @endif
            </div>
        </section>

        {{-- Totals --}}
        <section class="rounded-2xl bg-brand p-5 text-teal-50">
            <dl class="grid gap-2 text-sm tabular-nums">
                <div class="flex justify-between"><dt class="text-teal-100/75">Subtotal</dt><dd>{{ App\Support\Money::format($this->summary['subtotal']) }}</dd></div>
                @if ($this->summary['discount'] > 0)
                    <div class="flex justify-between"><dt class="text-teal-100/75">Discount</dt><dd>−{{ App\Support\Money::format($this->summary['discount']) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-white/15 pt-2 text-lg font-extrabold text-white"><dt>Total</dt><dd>{{ App\Support\Money::format($this->summary['total']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-teal-100/75">Paid now</dt><dd>{{ App\Support\Money::format($this->summary['paid']) }}</dd></div>
                <div class="flex justify-between font-bold {{ $this->summary['balance'] > 0 ? 'text-amber-300' : 'text-teal-300' }}">
                    <dt>{{ $this->summary['balance'] > 0 ? 'Balance on credit' : 'Balance' }}</dt><dd>{{ App\Support\Money::format($this->summary['balance']) }}</dd>
                </div>
            </dl>

            <x-button type="submit" variant="bright" size="lg" block target="complete" loading-text="Saving sale…" class="mt-5" :disabled="$this->cartProducts->isEmpty()">
                Complete sale
            </x-button>
            @if ($this->summary['balance'] > 0 && ! $this->customer && $this->cartProducts->isNotEmpty())
                <p class="mt-2 text-center text-[12.5px] text-amber-200">Choose a customer to leave a balance on credit.</p>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5">
            <x-textarea wire:model="notes" id="sale-notes" label="Notes (optional)" rows="2" placeholder="Delivery details, serial numbers…" />
        </section>
    </form>
</div>
