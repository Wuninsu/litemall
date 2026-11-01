<div class="h-full overflow-y-auto lg:grid lg:grid-cols-[minmax(0,1fr)_420px] lg:overflow-hidden xl:grid-cols-[minmax(0,1fr)_460px]"
     x-data
     x-on:keydown.window.slash="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) { $event.preventDefault(); $refs.search.focus() }">

    {{-- ===================== Products ===================== --}}
    <section class="flex min-h-0 flex-col lg:h-full" aria-label="Products">
        <div class="shrink-0 space-y-3 border-b border-slate-200/80 bg-white px-4 py-3 sm:px-5">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-slate-400" />
                <input x-ref="search" wire:model.live.debounce.200ms="productSearch" wire:keydown.enter.prevent="addFirstMatch"
                       id="product-search" type="search" autocomplete="off" autofocus
                       placeholder="Search or scan a product…  (press / to focus, Enter to add)"
                       class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-4 pl-12 text-base placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            </div>

            @if ($this->categories !== [])
                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-0.5" role="tablist" aria-label="Categories">
                    @foreach (['' => 'All', ...array_combine($this->categories, $this->categories)] as $value => $label)
                        <button type="button" wire:click="$set('category', @js($value))" role="tab" aria-selected="{{ $category === $value ? 'true' : 'false' }}"
                                @class([
                                    'shrink-0 rounded-full px-4 py-2 text-sm font-bold whitespace-nowrap transition',
                                    'bg-brand text-white shadow-sm' => $category === $value,
                                    'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 hover:ring-slate-400' => $category !== $value,
                                ])>{{ $label }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="min-h-0 flex-1 bg-slate-100 p-4 sm:p-5 lg:overflow-y-auto">
            @if ($this->productGrid->isEmpty())
                <div class="grid h-full place-items-center py-16 text-center text-slate-600">
                    <div class="grid justify-items-center gap-2">
                        <x-icon name="box" class="size-10 text-slate-400" />
                        <p class="font-semibold">No products match{{ $productSearch !== '' ? ' “'.$productSearch.'”' : '' }}.</p>
                    </div>
                </div>
            @else
                @php
                    // Solid colours for products without a photo, picked by category so similar items look alike.
                    $placeholderColours = ['bg-teal-600', 'bg-sky-600', 'bg-violet-600', 'bg-amber-500', 'bg-rose-500', 'bg-emerald-600', 'bg-indigo-600', 'bg-orange-500'];
                @endphp
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                    @foreach ($this->productGrid as $product)
                        @php
                            $inCart = (int) ($cart[$product->id] ?? 0);
                            $soldOut = $product->stock_quantity < 1;
                            $placeholder = $placeholderColours[crc32($product->category ?? $product->name) % count($placeholderColours)];
                        @endphp
                        <button type="button" wire:key="tile-{{ $product->id }}" wire:click="addProduct('{{ $product->id }}')" @disabled($soldOut)
                                @class([
                                    'group relative flex flex-col overflow-hidden rounded-2xl bg-white text-left shadow-sm transition focus:outline-none focus-visible:ring-4 focus-visible:ring-teal-600/30',
                                    'ring-2 ring-teal-500' => $inCart > 0,
                                    'ring-1 ring-slate-200 hover:-translate-y-0.5 hover:shadow-lg hover:ring-teal-400 active:translate-y-0' => $inCart === 0 && ! $soldOut,
                                    'cursor-not-allowed opacity-55 ring-1 ring-slate-200 grayscale' => $soldOut,
                                ])>
                            <span class="relative block aspect-[4/3] overflow-hidden bg-slate-200">
                                @if ($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover transition group-hover:scale-105" loading="lazy">
                                @else
                                    <span class="grid size-full place-items-center {{ $placeholder }}">
                                        <span class="px-3 text-center text-4xl font-extrabold text-white/90">{{ str($product->name)->squish()->explode(' ')->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') }}</span>
                                    </span>
                                @endif

                                @if ($inCart > 0)
                                    <span class="absolute top-2 right-2 grid min-w-8 place-items-center rounded-full bg-teal-700 px-2 py-1 text-sm font-extrabold text-white shadow-lg ring-2 ring-white">{{ $inCart }}</span>
                                @endif

                                @if ($soldOut)
                                    <span class="absolute inset-x-0 bottom-0 bg-red-600 py-1.5 text-center text-xs font-bold text-white">Out of stock</span>
                                @elseif ($product->stockLevel() === 'low')
                                    <span class="absolute bottom-2 left-2 rounded-full bg-amber-400 px-2.5 py-0.5 text-xs font-bold text-amber-950 shadow">Only {{ $product->stock_quantity }} left</span>
                                @endif
                            </span>

                            <span class="flex flex-1 flex-col gap-2 p-3.5">
                                <span class="line-clamp-2 text-[15px] leading-snug font-bold text-slate-900">{{ $product->name }}</span>
                                <span class="mt-auto flex items-end justify-between gap-2">
                                    @if ($priceTier === 'wholesale' && $product->hasWholesalePrice())
                                        <span class="grid">
                                            <span class="text-sm font-extrabold text-amber-700 tabular-nums">{{ $product->formattedWholesalePrice() }}</span>
                                            <span class="text-xs text-slate-500 tabular-nums">
                                                <s>{{ $product->formattedPrice() }}</s>{{ $product->wholesale_min_quantity > 1 ? ' · from '.$product->wholesale_min_quantity : '' }}
                                            </span>
                                        </span>
                                    @else
                                        <span class="text-sm font-extrabold text-slate-900 tabular-nums">{{ $product->formattedPrice() }}</span>
                                    @endif
                                    @if (! $soldOut && $product->stockLevel() === 'in')
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600 tabular-nums">{{ $product->stock_quantity }} left</span>
                                    @endif
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
                @if ($this->productGrid->count() >= App\Livewire\Sales\Create::GRID_LIMIT)
                    <p class="mt-4 text-center text-sm font-semibold text-slate-600">Showing the first {{ App\Livewire\Sales\Create::GRID_LIMIT }} products — search to find others.</p>
                @endif
            @endif
        </div>
    </section>

    {{-- ===================== Cart ===================== --}}
    <aside class="flex min-h-0 flex-col border-l border-slate-200/80 bg-white lg:h-full" aria-label="Current sale">
        {{-- Customer --}}
        <div class="shrink-0 border-b border-slate-100 p-4" x-data="{ open: false }" x-on:click.outside="open = false">
            @if ($this->customer)
                <div class="flex items-center gap-3 rounded-2xl bg-teal-50 p-3 ring-1 ring-teal-200">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-teal-700 text-white"><x-icon name="people" class="size-5" /></span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="truncate font-bold">{{ $this->customer->name }}@if ($this->customer->is_wholesale) <span class="ml-1 rounded bg-amber-100 px-1.5 py-px align-middle text-[10.5px] font-bold text-amber-800">Wholesale</span>@endif</p>
                        <p class="text-[13px] text-slate-600">{{ $this->customer->formattedPhone() }}
                            @if ($this->customer->outstandingPesewas() > 0)
                                · <span class="font-semibold text-amber-700">owes {{ App\Support\Money::format($this->customer->outstandingPesewas()) }}</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="clearCustomer" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-white hover:text-slate-800" title="Sell to a walk-in customer instead">
                        Walk-in
                    </button>
                </div>
            @else
                {{-- No customer chosen: the sale is a walk-in sale (must be paid in full). --}}
                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-200 text-slate-600"><x-icon name="people" class="size-5" /></span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-bold">Walk-in customer</p>
                        <p class="text-[13px] text-slate-500">Pays in full now</p>
                    </div>
                    <button type="button" x-on:click="open = ! open; $nextTick(() => $refs.customerSearch?.focus())"
                            class="rounded-lg bg-white px-2.5 py-1.5 text-xs font-bold text-teal-700 ring-1 ring-slate-200 hover:ring-teal-300">Choose</button>
                    <button type="button" wire:click="openNewCustomer"
                            class="flex items-center gap-1 rounded-lg bg-teal-700 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-teal-800">
                        <x-icon name="plus" class="size-3.5" /> New
                    </button>
                </div>

                <div x-show="open" x-cloak x-transition.opacity class="relative">
                    <div class="absolute inset-x-0 top-2 z-20 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <input x-ref="customerSearch" wire:model.live.debounce.250ms="customerSearch" id="customer-search" type="search" autocomplete="off"
                               placeholder="Name or phone number" aria-label="Search customers"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                        <ul class="mt-1 max-h-64 overflow-y-auto">
                            @forelse ($this->customerResults as $result)
                                <li wire:key="customer-{{ $result->id }}">
                                    <button type="button" wire:click="selectCustomer('{{ $result->id }}')" x-on:click="open = false"
                                            class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-teal-50">
                                        <span>
                                            <span class="block font-semibold">{{ $result->name }}@if ($result->is_wholesale) <span class="ml-1 rounded bg-amber-100 px-1.5 py-px text-[10.5px] font-bold text-amber-800">Wholesale</span>@endif</span>
                                            <span class="block text-xs text-slate-500">{{ $result->formattedPhone() }}</span>
                                        </span>
                                        @if ($result->outstandingPesewas() > 0)
                                            <span class="text-xs font-semibold text-amber-700">owes {{ App\Support\Money::format($result->outstandingPesewas()) }}</span>
                                        @endif
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-3 text-center text-sm text-slate-500">
                                    {{ $customerSearch === '' ? 'Start typing to find a customer.' : 'No active customer found.' }}
                                </li>
                            @endforelse
                        </ul>
                        <button type="button" wire:click="openNewCustomer" x-on:click="open = false"
                                class="mt-1 flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-teal-300 px-3 py-2 text-sm font-bold text-teal-700 hover:bg-teal-50">
                            <x-icon name="plus" class="size-4" />
                            New customer{{ $customerSearch !== '' ? ' “'.$customerSearch.'”' : '' }}
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Items, discount, payment (scrolls) --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="flex items-center justify-between gap-2 px-4 pt-3 pb-1">
                <h2 class="text-sm font-bold">Current sale <span class="font-semibold text-slate-400">· {{ $this->summary['items'] }} {{ Str::plural('item', $this->summary['items']) }}</span></h2>
                <div class="ml-auto inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-bold" role="radiogroup" aria-label="Price list">
                    @foreach (App\Enums\PriceTier::cases() as $tierOption)
                        <button type="button" wire:click="setPriceTier('{{ $tierOption->value }}')" role="radio" aria-checked="{{ $priceTier === $tierOption->value ? 'true' : 'false' }}"
                                @class([
                                    'rounded-md px-2.5 py-1 transition',
                                    'bg-white text-slate-900 shadow-sm' => $priceTier === $tierOption->value && $tierOption->value === 'retail',
                                    'bg-amber-500 text-white shadow-sm' => $priceTier === $tierOption->value && $tierOption->value === 'wholesale',
                                    'text-slate-500 hover:text-slate-800' => $priceTier !== $tierOption->value,
                                ])>{{ $tierOption->label() }}</button>
                    @endforeach
                </div>
                @if ($cart !== [])
                    <button type="button" wire:click="clearCart" wire:confirm="Remove all items from this sale?" class="text-xs font-semibold text-slate-400 hover:text-red-600">Clear</button>
                @endif
            </div>
            @if ($priceTier === 'wholesale')
                <p class="mx-4 mt-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                    Wholesale prices apply where set and the minimum quantity is reached; other items use retail.
                </p>
            @endif

            @if ($this->cartProducts->isEmpty())
                <div class="grid justify-items-center gap-2 px-4 py-10 text-center text-slate-400">
                    <x-icon name="cart" class="size-10 text-slate-300" />
                    <p class="text-sm">Tap a product to add it.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 px-4">
                    @foreach ($this->cartProducts as $product)
                        @php $quantity = max(0, (int) ($cart[$product->id] ?? 0)); @endphp
                        @php $linePrice = $this->unitPrice($product, $quantity); @endphp
                        <li wire:key="line-{{ $product->id }}" class="flex items-center gap-3 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500 tabular-nums">
                                    {{ App\Support\Money::format($linePrice['pesewas']) }} each
                                    @if ($linePrice['tier'] === App\Enums\PriceTier::Wholesale)
                                        <span class="ml-1 rounded bg-amber-100 px-1.5 py-px text-[10.5px] font-bold text-amber-800">Wholesale</span>
                                    @elseif ($priceTier === 'wholesale' && $product->hasWholesalePrice())
                                        <span class="ml-1 text-[11px] text-amber-700">wholesale from {{ $product->wholesale_min_quantity }}</span>
                                    @endif
                                </p>
                                @error('cart.'.$product->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center rounded-xl border border-slate-200">
                                <button type="button" wire:click="decrement('{{ $product->id }}')" class="grid size-8 place-items-center text-slate-500 hover:text-slate-900" aria-label="One less {{ $product->name }}">
                                    <x-icon name="minus" class="size-4" />
                                </button>
                                <input wire:model.live.debounce.400ms="cart.{{ $product->id }}" type="number" min="1" max="{{ max(1, $product->stock_quantity) }}" inputmode="numeric"
                                       aria-label="Quantity of {{ $product->name }}"
                                       class="h-8 w-10 border-0 bg-transparent p-0 text-center text-sm font-bold tabular-nums [appearance:textfield] focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" wire:click="increment('{{ $product->id }}')" @disabled($quantity >= $product->stock_quantity)
                                        class="grid size-8 place-items-center text-slate-500 hover:text-slate-900 disabled:opacity-30" aria-label="One more {{ $product->name }}">
                                    <x-icon name="plus" class="size-4" />
                                </button>
                            </div>
                            <p class="w-24 text-right text-sm font-bold tabular-nums">{{ App\Support\Money::format($linePrice['pesewas'] * $quantity) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Discount --}}
            <div class="border-t border-slate-100 px-4 py-3">
                @if (! $showDiscount && trim($discountValue) === '')
                    <button type="button" wire:click="$set('showDiscount', true)" class="flex items-center gap-2 text-sm font-semibold text-teal-700 hover:text-teal-800">
                        <x-icon name="tag" class="size-4" /> Add discount
                    </button>
                @else
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm font-bold"><x-icon name="tag" class="size-4 text-teal-700" /> Discount</span>
                            <div class="inline-flex rounded-lg border border-slate-200 p-0.5 text-xs font-bold" role="radiogroup" aria-label="Discount type">
                                <label class="cursor-pointer rounded-md px-2.5 py-1 has-checked:bg-teal-700 has-checked:text-white">
                                    <input type="radio" wire:model.live="discountType" value="amount" class="sr-only"> GH₵
                                </label>
                                <label class="cursor-pointer rounded-md px-2.5 py-1 has-checked:bg-teal-700 has-checked:text-white">
                                    <input type="radio" wire:model.live="discountType" value="percent" class="sr-only"> %
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-[120px_1fr] gap-2">
                            <input wire:model.live.debounce.400ms="discountValue" id="discount-value" inputmode="decimal" autocomplete="off" placeholder="{{ $discountType === 'percent' ? '0 %' : '0.00' }}"
                                   aria-label="{{ $discountType === 'percent' ? 'Discount percent' : 'Discount amount in cedis' }}"
                                   class="h-10 rounded-xl border border-slate-300 px-3 text-sm tabular-nums focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                            <input wire:model="discountReason" id="discount-reason" placeholder="Reason (optional)" aria-label="Discount reason"
                                   class="h-10 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                        </div>
                        @error('discountValue') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($this->summary['discountError'])
                            <p class="text-xs text-red-600">{{ $this->summary['discountError'] }}</p>
                        @elseif (! auth()->user()->isAdmin())
                            <p class="text-xs text-slate-500">Cashiers can give up to {{ rtrim(rtrim(number_format($cashierLimit, 2), '0'), '.') }}%.</p>
                        @endif
                    </div>
                @endif
            </div>

        </div>

        {{-- Totals + charge (pinned) --}}
        <div class="shrink-0 border-t border-slate-200 bg-slate-50 p-4">
            <dl class="grid gap-1 text-sm tabular-nums">
                <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd>{{ App\Support\Money::format($this->summary['subtotal']) }}</dd></div>
                @if ($this->summary['discount'] > 0)
                    <div class="flex justify-between text-slate-500"><dt>Discount</dt><dd>−{{ App\Support\Money::format($this->summary['discount']) }}</dd></div>
                @endif
                <div class="flex items-baseline justify-between pt-1"><dt class="font-bold">Total</dt><dd class="text-2xl font-extrabold">{{ App\Support\Money::format($this->summary['total']) }}</dd></div>
            </dl>

            @foreach (['lines', 'cart', 'customer', 'discount'] as $field)
                @error($field) <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-[13px] text-red-700">{{ $message }}</p> @enderror
            @endforeach

            <x-button size="lg" block wire:click="openPayment" target="openPayment" class="mt-3 h-14! justify-between! rounded-2xl px-5! text-base!" :disabled="$this->cartProducts->isEmpty()">
                <span>Charge</span>
                <span class="tabular-nums">{{ App\Support\Money::format($this->summary['total']) }}</span>
            </x-button>
        </div>
    </aside>

    {{-- ===================== Checkout: payment method and amount received ===================== --}}
    <x-modal wire:model="showPaymentModal" title="Take payment" :subtitle="($this->customer?->name ?? 'Walk-in customer').' · '.$this->summary['items'].' '.Str::plural('item', $this->summary['items'])" max-width="max-w-lg">
        <form wire:submit="complete" class="flex min-h-0 flex-1 flex-col">
            <div class="panel-scroll grid min-h-0 flex-1 content-start gap-3.5 px-5 py-4">
                <div class="flex items-center justify-between rounded-xl bg-brand px-4 py-2.5 text-teal-50">
                    <span class="text-sm font-semibold text-teal-200">
                        Amount due
                        @if ($this->summary['discount'] > 0)
                            <span class="block text-[11px] font-medium text-teal-100/70">after {{ App\Support\Money::format($this->summary['discount']) }} discount</span>
                        @endif
                    </span>
                    <span class="text-2xl font-extrabold text-white tabular-nums">{{ App\Support\Money::format($this->summary['total']) }}</span>
                </div>

                {{-- Payment methods: one flexible row of equal-width pills --}}
                <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Payment method">
                    @foreach ($methods as $method)
                        <label @class([
                            'flex min-w-[6.5rem] flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-2 py-2 text-xs font-bold whitespace-nowrap transition has-focus-visible:ring-4 has-focus-visible:ring-teal-600/20',
                            'border-teal-600 bg-teal-50 text-teal-800 ring-1 ring-teal-600' => $paymentMethod === $method->value,
                            'border-slate-200 text-slate-600 hover:border-slate-300' => $paymentMethod !== $method->value,
                        ])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $method->value }}" class="sr-only">
                            <x-icon :name="$method->icon()" class="size-4" />
                            {{ $method->label() }}
                        </label>
                    @endforeach
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between">
                        <label for="payment-amount" class="text-[13px] font-semibold text-slate-700">{{ $paymentMethod === 'cash' ? 'Cash received' : 'Amount paid' }}</label>
                        <button type="button" wire:click="payInFull" class="text-xs font-bold text-teal-700 hover:text-teal-800">Pay in full</button>
                    </div>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-slate-500">GH₵</span>
                        <input wire:model.live.debounce.300ms="paymentAmount" id="payment-amount" inputmode="decimal" autocomplete="off" placeholder="0.00"
                               x-init="$wire.$watch('showPaymentModal', open => open && $nextTick(() => { $el.focus(); $el.select() }))"
                               class="h-12 w-full rounded-xl border border-slate-300 pr-3.5 pl-14 text-xl font-extrabold tabular-nums focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                    </div>
                    @error('paymentAmount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    @if ($paymentMethod === 'cash' && $this->quickCashOptions !== [])
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($this->quickCashOptions as $option)
                                <button type="button" wire:key="cash-{{ $option }}" wire:click="setPaymentAmount({{ $option }})"
                                        @class([
                                            'flex-1 rounded-lg px-2.5 py-1.5 text-xs font-bold whitespace-nowrap tabular-nums transition',
                                            'bg-teal-700 text-white' => $this->summary['received'] === $option,
                                            'bg-slate-100 text-slate-700 hover:bg-teal-100 hover:text-teal-900' => $this->summary['received'] !== $option,
                                        ])>
                                    {{ $loop->first ? 'Exact' : App\Support\Money::format($option) }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (App\Enums\PaymentMethod::tryFrom($paymentMethod)?->expectsReference())
                    <input wire:model="paymentReference" id="payment-reference" placeholder="Transaction reference (optional), e.g. MoMo ID" aria-label="Transaction reference"
                           class="h-10 rounded-xl border border-slate-300 px-3 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                @endif

                @if ($this->summary['change'] > 0)
                    <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-2 ring-1 ring-emerald-200">
                        <span class="text-sm font-semibold text-emerald-800">Change due</span>
                        <span class="text-lg font-extrabold text-emerald-700 tabular-nums">{{ App\Support\Money::format($this->summary['change']) }}</span>
                    </div>
                @elseif ($this->summary['balance'] > 0)
                    <div class="rounded-xl bg-amber-50 px-4 py-2 ring-1 ring-amber-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-amber-800">{{ $this->customer ? 'Balance on credit' : 'Still to pay' }}</span>
                            <span class="text-lg font-extrabold text-amber-700 tabular-nums">{{ App\Support\Money::format($this->summary['balance']) }}</span>
                        </div>
                        @unless ($this->customer)
                            <p class="text-xs text-amber-700">Walk-in sales must be paid in full. Choose a customer to sell on credit.</p>
                        @endunless
                    </div>
                @endif

                <details class="text-sm">
                    <summary class="cursor-pointer text-[13px] font-semibold text-slate-500 hover:text-slate-700">Add a note</summary>
                    <textarea wire:model="notes" id="sale-notes" rows="2" placeholder="Delivery details, serial numbers…" aria-label="Sale notes"
                              class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none"></textarea>
                </details>

                @foreach (['lines', 'cart', 'customer', 'discount', 'payment'] as $field)
                    @error($field) <p class="rounded-lg bg-red-50 px-3 py-2 text-[13px] text-red-700">{{ $message }}</p> @enderror
                @endforeach
            </div>

            <div class="flex shrink-0 gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <x-button variant="ghost" x-on:click="open = false" :loading="false">Back</x-button>
                <x-button type="submit" target="complete" loading-text="Saving sale…" class="flex-1 justify-between! px-4!">
                    <span>Complete sale</span>
                    <span class="tabular-nums">{{ App\Support\Money::format($this->summary['paid']) }}</span>
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- ===================== New customer (stays on the POS) ===================== --}}
    <x-modal wire:model="showCustomerModal" title="New customer" subtitle="They will be selected for this sale." max-width="max-w-md">
        <form wire:submit="saveNewCustomer" class="flex min-h-0 flex-1 flex-col">
            <div class="panel-scroll grid min-h-0 flex-1 content-start gap-4 px-6 py-5">
                <x-input wire:model="customerForm.name" id="new-customer-name" label="Full name" placeholder="e.g. Akua Serwaa" required autofocus />
                <x-input wire:model="customerForm.phone" id="new-customer-phone" type="tel" label="Phone number" placeholder="024 412 3456" inputmode="tel" autocomplete="off" required />
                <x-input wire:model="customerForm.address" id="new-customer-address" label="Location (optional)" placeholder="e.g. Madina, Accra" />
                <x-input wire:model="customerForm.email" id="new-customer-email" type="email" label="Email (optional)" />
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="customerForm.isWholesale" type="checkbox" class="size-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500/30">
                    Wholesale customer <span class="text-slate-400">(uses wholesale prices automatically)</span>
                </label>
            </div>
            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <x-button variant="ghost" wire:click="closeNewCustomer" :loading="false">Cancel</x-button>
                <x-button type="submit" target="saveNewCustomer" loading-text="Saving…">Add and select</x-button>
            </div>
        </form>
    </x-modal>

    {{-- ===================== Sale complete: animated receipt printer ===================== --}}
    @if ($this->completedSale)
        @php $done = $this->completedSale; @endphp
        <div wire:key="sale-complete-{{ $done->id }}" class="fixed inset-0 z-50 overflow-y-auto bg-brand/90 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="sale-complete-title"
             x-data x-init="$nextTick(() => $refs.next.focus())">
            <div class="mx-auto grid min-h-full max-w-3xl items-start gap-6 px-4 py-8 md:grid-cols-[minmax(0,1fr)_280px] md:items-center">
                <x-receipt-printer :sale="$done" :change-due="$changeDue" />

                <div class="rounded-3xl bg-white p-6 text-center shadow-2xl md:sticky md:top-8">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="check" class="size-6" /></span>
                    <h2 id="sale-complete-title" class="mt-3 text-xl font-extrabold">Sale complete</h2>
                    <p class="mt-0.5 font-mono text-sm text-slate-500">{{ $done->receipt_number }}{{ $done->customer ? ' · '.$done->customer->name : '' }}</p>

                    @if ($changeDue > 0)
                        <div class="mt-4 rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
                            <p class="text-sm font-semibold text-emerald-800">Change due</p>
                            <p class="text-3xl font-extrabold text-emerald-700 tabular-nums">{{ App\Support\Money::format($changeDue) }}</p>
                        </div>
                    @elseif ($done->balancePesewas() > 0)
                        <div class="mt-4 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
                            <p class="text-sm font-semibold text-amber-800">On credit</p>
                            <p class="text-3xl font-extrabold text-amber-700 tabular-nums">{{ $done->money('balance') }}</p>
                        </div>
                    @else
                        <p class="mt-4 rounded-2xl bg-slate-50 p-3 text-sm font-semibold text-slate-600">Paid in full · {{ $done->money('paid') }}</p>
                    @endif

                    <div class="mt-5 grid gap-2">
                        <x-button size="lg" icon="plus" wire:click="newSale" x-ref="next" block>New sale</x-button>
                        <a href="{{ route('sales.show', $done) }}" target="_blank" rel="noopener"
                           class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-400/30">
                            <x-icon name="receipt" class="size-4" /> Print receipt
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
