<div class="grid gap-5">
    <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <x-button variant="subtle" size="sm" icon="arrow-left" :href="route('sales.index')">All sales</x-button>
        <div class="flex flex-wrap gap-2">
            @if ($sale->customer && $sale->balancePesewas() > 0)
                <x-button variant="secondary" icon="wallet" :href="route('payments.index', ['customer' => $sale->customer_id])">Receive payment</x-button>
            @endif
            <x-button variant="ghost" icon="receipt" x-on:click="window.print()" :loading="false">Print receipt</x-button>
            <x-button icon="plus" :href="route('sales.create')">New sale</x-button>
        </div>
    </div>

    <article class="mx-auto w-full max-w-3xl rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 print:max-w-none print:rounded-none print:border-0 print:p-0">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-6">
            <div>
                <p class="text-xl font-extrabold">{{ config('app.name') }}</p>
                <p class="text-sm text-slate-500">Sales receipt</p>
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
                            <p class="font-semibold">{{ $line->product_name }}</p>
                            @if ($line->product_sku)
                                <p class="font-mono text-xs text-slate-500">{{ $line->product_sku }}</p>
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
            <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ $sale->money('paid') }}</dd></div>
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

        @if ($sale->notes)
            <p class="mt-6 text-sm text-slate-600"><span class="font-semibold">Notes:</span> {{ $sale->notes }}</p>
        @endif

        <p class="mt-10 text-center text-xs text-slate-400">Thank you for your business.</p>
    </article>
</div>
