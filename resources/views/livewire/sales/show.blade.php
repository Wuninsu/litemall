<div class="grid gap-5">
    @php
        $canRefund = auth()->user()->can('refund', $sale);
        $returnable = $sale->lines->sum(fn ($line) => $line->returnableQuantity());
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <x-button variant="subtle" size="sm" icon="arrow-left" :href="route('sales.index')">All sales</x-button>
        <div class="flex flex-wrap gap-2">
            @if ($sale->customer && $sale->balancePesewas() > 0)
                <x-button variant="secondary" icon="wallet" :href="route('payments.index', ['customer' => $sale->customer_id])">Receive payment</x-button>
            @endif
            @if (! $sale->isCancelled() && $returnable > 0)
                @if ($canRefund)
                    <x-button variant="danger" icon="arrow-left" wire:click="openReturn">Return items</x-button>
                @else
                    <span class="inline-flex h-10 cursor-not-allowed items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-400" title="Ask an administrator to return items or cancel a sale">
                        <x-icon name="arrow-left" class="size-4" /> Return items
                    </span>
                @endif
            @endif
            <x-button variant="ghost" icon="receipt" x-on:click="window.print()" :loading="false">Print receipt</x-button>
            <x-button icon="plus" :href="route('sales.create')">New sale</x-button>
        </div>
    </div>

    @if ($notice)
        <div wire:key="notice-{{ md5($notice) }}" class="mx-auto flex w-full max-w-3xl items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900 print:hidden" role="status">
            <x-icon name="check" class="size-4" />
            {{ $notice }}
        </div>
    @endif

    <article class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 print:max-w-none print:rounded-none print:border-0 print:p-0">
        @if ($sale->isCancelled())
            <span aria-hidden="true" class="pointer-events-none absolute top-1/3 left-1/2 -translate-x-1/2 -rotate-12 rounded-xl border-4 border-red-500/40 px-6 py-2 text-5xl font-extrabold tracking-widest text-red-500/30 uppercase">Cancelled</span>
        @endif

        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-6">
            <div>
                <p class="text-xl font-extrabold">{{ config('app.name') }}</p>
                <p class="text-sm text-slate-500">Sales receipt{{ $sale->price_tier?->value === 'wholesale' ? ' · Wholesale' : '' }}</p>
            </div>
            <div class="text-right">
                <p class="font-mono text-lg font-bold">{{ $sale->receipt_number }}</p>
                <p class="text-sm text-slate-500">{{ $sale->sold_at->format('j M Y, g:i a') }}</p>
                <x-sale-status :status="$sale->status" class="mt-2" />
            </div>
        </header>

        <dl class="grid gap-4 border-b border-dashed border-slate-200 py-6 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">Customer</dt>
                <dd class="font-semibold">{{ $sale->customer?->name ?? 'Walk-in customer' }}</dd>
                @if ($sale->customer)
                    <dd class="text-slate-600">{{ $sale->customer->formattedPhone() }}</dd>
                @endif
            </div>
            <div class="sm:text-right">
                <dt class="text-slate-500">Served by</dt>
                <dd class="font-semibold">{{ $sale->seller->name }}</dd>
            </div>
        </dl>

        <table class="my-6 w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-[11.5px] font-bold tracking-wider text-slate-400 uppercase">
                    <th class="py-2">Item</th>
                    <th class="py-2 text-right">Unit price</th>
                    <th class="py-2 text-right">Qty</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($sale->lines as $line)
                    <tr wire:key="line-{{ $line->id }}">
                        <td class="py-2.5">
                            <p class="font-semibold">{{ $line->product_name }}@if ($line->price_tier?->value === 'wholesale') <span class="ml-1 rounded bg-amber-100 px-1.5 py-px text-[10.5px] font-bold text-amber-800 print:border print:border-slate-300">Wholesale</span>@endif</p>
                            @if ($line->product_sku)
                                <p class="font-mono text-xs text-slate-500">{{ $line->product_sku }}</p>
                            @endif
                            @if ($line->returned_quantity > 0)
                                <p class="text-xs font-semibold text-red-600">{{ $line->returned_quantity }} returned</p>
                            @endif
                        </td>
                        <td class="py-2.5 text-right tabular-nums">{{ $line->formattedUnitPrice() }}</td>
                        <td class="py-2.5 text-right tabular-nums">{{ $line->quantity }}</td>
                        <td class="py-2.5 text-right font-semibold tabular-nums">{{ $line->formattedLineTotal() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="ml-auto grid max-w-xs gap-1.5 border-t border-slate-200 pt-4 text-sm tabular-nums">
            <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ $sale->money('subtotal') }}</dd></div>
            @if ($sale->discount_pesewas > 0)
                <div class="flex justify-between"><dt class="text-slate-500">Discount{{ $sale->discount_reason ? ' ('.$sale->discount_reason.')' : '' }}</dt><dd>−{{ $sale->money('discount') }}</dd></div>
            @endif
            <div class="flex justify-between border-t border-slate-200 pt-1.5 text-base font-extrabold"><dt>Total</dt><dd>{{ $sale->money('total') }}</dd></div>
            @if ($sale->returned_pesewas > 0)
                <div class="flex justify-between text-red-600"><dt>Returned</dt><dd>−{{ $sale->money('returned') }}</dd></div>
                <div class="flex justify-between font-bold"><dt>Net total</dt><dd>{{ $sale->money('net') }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-slate-500">Paid{{ $sale->returns->sum('refund_pesewas') > 0 ? ' (after refunds)' : '' }}</dt><dd>{{ $sale->money('paid') }}</dd></div>
            <div class="flex justify-between font-bold {{ $sale->balancePesewas() > 0 ? 'text-amber-700' : 'text-emerald-700' }}"><dt>Balance</dt><dd>{{ $sale->money('balance') }}</dd></div>
        </dl>

        @if ($sale->allocations->isNotEmpty())
            <section class="mt-8 border-t border-dashed border-slate-200 pt-6">
                <h2 class="mb-3 text-sm font-bold">Payments towards this sale</h2>
                <ul class="grid gap-2 text-sm">
                    @foreach ($sale->allocations->sortBy(fn ($allocation) => $allocation->payment->received_at) as $allocation)
                        <li wire:key="allocation-{{ $allocation->id }}" class="flex flex-wrap justify-between gap-2">
                            <span class="text-slate-600">
                                {{ $allocation->payment->received_at->format('j M Y, g:i a') }} · {{ $allocation->payment->method->label() }}
                                @if ($allocation->payment->reference) · <span class="font-mono">{{ $allocation->payment->reference }}</span>@endif
                                · {{ $allocation->payment->receiver->name }}
                            </span>
                            <span class="font-semibold tabular-nums">{{ $allocation->formattedAmount() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($sale->returns->isNotEmpty())
            <section class="mt-8 border-t border-dashed border-slate-200 pt-6">
                <h2 class="mb-3 text-sm font-bold">Returns and cancellations</h2>
                <ul class="grid gap-3 text-sm">
                    @foreach ($sale->returns->sortBy('returned_at') as $saleReturn)
                        <li wire:key="return-{{ $saleReturn->id }}" class="rounded-xl bg-red-50/50 p-3 ring-1 ring-red-100">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span>
                                    <span class="font-mono font-bold">{{ $saleReturn->return_number }}</span>
                                    · {{ $saleReturn->isCancellation() ? 'Cancellation' : 'Return' }}
                                    · {{ $saleReturn->returned_at->format('j M Y, g:i a') }} · {{ $saleReturn->processor->name }}
                                </span>
                                <span class="font-semibold text-red-700 tabular-nums">−{{ $saleReturn->money('credit') }}</span>
                            </div>
                            <p class="mt-1 text-slate-600">“{{ $saleReturn->reason }}”</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $saleReturn->lines->map(fn ($returnLine) => $returnLine->quantity.' × '.$returnLine->saleLine->product_name.($returnLine->restocked ? ' (back to stock)' : ' (not restocked)'))->implode(', ') }}
                            </p>
                            <p class="mt-1 text-xs text-slate-600">
                                @if ($saleReturn->debt_reduction_pesewas > 0) Debt reduced {{ $saleReturn->money('debt_reduction') }}. @endif
                                @if ($saleReturn->refund_pesewas > 0) Refunded {{ $saleReturn->money('refund') }} by {{ $saleReturn->refund_method?->label() }}{{ $saleReturn->refund_reference ? ' ('.$saleReturn->refund_reference.')' : '' }}. @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($sale->notes)
            <p class="mt-6 text-sm text-slate-600"><span class="font-semibold">Notes:</span> {{ $sale->notes }}</p>
        @endif

        <p class="mt-10 text-center text-xs text-slate-400">Thank you for your business.</p>
    </article>

    {{-- Return / cancel panel (administrators only) --}}
    @if ($canRefund)
        <x-modal wire:model="showReturnModal" position="right" max-width="max-w-xl" title="Return items" :subtitle="$sale->receipt_number.' · '.($sale->customer?->name ?? 'Walk-in customer')">
            <form wire:submit="saveReturn" class="flex min-h-0 flex-1 flex-col">
                <div class="panel-scroll grid min-h-0 flex-1 content-start gap-6 overflow-y-auto px-6 py-6">
                    <section class="grid gap-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Items to return</h3>
                            <button type="button" wire:click="returnEverything" class="text-xs font-bold text-red-700 hover:text-red-800">Return everything (cancel sale)</button>
                        </div>
                        <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                            @foreach ($sale->lines as $line)
                                <li wire:key="return-line-{{ $line->id }}" @class(['grid gap-2 px-4 py-3 text-sm', 'opacity-50' => $line->returnableQuantity() === 0])>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold">{{ $line->product_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $line->formattedUnitPrice() }} each · sold {{ $line->quantity }}{{ $line->returned_quantity > 0 ? ', '.$line->returned_quantity.' already returned' : '' }}</p>
                                        </div>
                                        @if ($line->returnableQuantity() > 0)
                                            <input wire:model.live.debounce.300ms="returnQuantities.{{ $line->id }}" type="number" min="0" max="{{ $line->returnableQuantity() }}" step="1" inputmode="numeric"
                                                   aria-label="Units of {{ $line->product_name }} to return"
                                                   class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-center text-sm font-bold tabular-nums focus:border-red-500 focus:ring-4 focus:ring-red-500/15 focus:outline-none">
                                        @else
                                            <span class="text-xs font-semibold text-slate-400">All returned</span>
                                        @endif
                                    </div>
                                    @if ($line->returnableQuantity() > 0 && (int) ($returnQuantities[$line->id] ?? 0) > 0)
                                        <label class="flex items-center gap-2 text-[13px] text-slate-600">
                                            <input wire:model="restock.{{ $line->id }}" type="checkbox" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600/30">
                                            Put back into stock (untick if damaged or faulty)
                                        </label>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @error('lines') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                        @error('returnQuantities.*') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                    </section>

                    <section class="grid gap-2">
                        <label for="return-reason" class="text-[13px] font-semibold text-slate-700">Reason</label>
                        <textarea wire:model="reason" id="return-reason" rows="2" placeholder="e.g. Faulty on arrival / entered by mistake"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-red-500 focus:ring-4 focus:ring-red-500/15 focus:outline-none"></textarea>
                        @error('reason') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                    </section>

                    @if ($this->returnPreview)
                        @if ($this->returnPreview['error'])
                            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $this->returnPreview['error'] }}</p>
                        @else
                            <section class="grid gap-2 rounded-2xl bg-slate-50 p-4 text-sm tabular-nums ring-1 ring-slate-200">
                                <div class="flex justify-between"><span class="text-slate-500">Value returned</span><span class="font-bold">{{ App\Support\Money::format($this->returnPreview['credit']) }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-500">Reduces what is owed</span><span class="font-semibold">{{ App\Support\Money::format($this->returnPreview['debtReduction']) }}</span></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-extrabold {{ $this->returnPreview['refund'] > 0 ? 'text-red-700' : '' }}">
                                    <span>Refund to customer</span><span>{{ App\Support\Money::format($this->returnPreview['refund']) }}</span>
                                </div>
                                @if ($sale->discount_pesewas > 0)
                                    <p class="text-xs text-slate-500">Values include this item's share of the sale discount.</p>
                                @endif
                                @if ($this->returnPreview['isCancellation'])
                                    <p class="text-xs font-semibold text-red-700">Everything left on the sale is being returned — the sale will be marked Cancelled.</p>
                                @endif
                            </section>

                            @if ($this->returnPreview['refund'] > 0)
                                <section class="grid gap-3 sm:grid-cols-2">
                                    <div class="grid gap-1.5">
                                        <label for="refund-method" class="text-[13px] font-semibold text-slate-700">Refund paid by</label>
                                        <select wire:model="refundMethod" id="refund-method" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                                            @foreach ($methods as $method)
                                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('refundMethod') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <x-input wire:model="refundReference" id="refund-reference" label="Reference (optional)" placeholder="e.g. MoMo ID" />
                                </section>
                            @endif
                        @endif
                    @endif
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <x-button variant="ghost" x-on:click="open = false" :loading="false">Close</x-button>
                    <x-button type="submit" variant="danger" target="saveReturn" loading-text="Saving…" class="bg-red-600! text-white! hover:bg-red-700!">
                        {{ ($this->returnPreview['isCancellation'] ?? false) ? 'Cancel sale' : 'Record return' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
