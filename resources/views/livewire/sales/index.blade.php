<div class="grid gap-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" id="sale-search" placeholder="Receipt number or customer" aria-label="Search sales"
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
        </div>

        <select wire:model.live="period" id="sale-period-filter" aria-label="Period"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="today">Today</option>
            <option value="7days">Last 7 days</option>
            <option value="30days">Last 30 days</option>
            <option value="">All time</option>
        </select>

        <select wire:model.live="status" id="sale-status-filter" aria-label="Payment status"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">Any status</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </select>

        <x-button icon="plus" :href="route('sales.create')" class="sm:ml-auto">New sale</x-button>
    </div>

    <section aria-label="Totals for these sales" class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Sales', 'value' => number_format($totals['count'])],
            ['label' => 'Sold', 'value' => App\Support\Money::format($totals['total'])],
            ['label' => 'Still owed', 'value' => App\Support\Money::format($totals['total'] - $totals['paid'])],
        ] as $tile)
            <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4">
                <p class="text-[13px] font-semibold text-slate-500">{{ $tile['label'] }}</p>
                <p class="mt-1 text-xl font-extrabold tabular-nums">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11.5px] font-bold tracking-wider text-slate-400 uppercase">
                        <th class="px-5 py-3">Receipt</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('sales.show', $sale) }}" wire:navigate class="font-mono font-semibold text-teal-800 hover:underline">{{ $sale->receipt_number }}</a>
                                <p class="text-xs text-slate-500">{{ $sale->lines_count }} {{ Str::plural('item', $sale->lines_count) }} · {{ $sale->seller->name }}</p>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $sale->sold_at->format('j M Y, g:i a') }}</td>
                            <td class="px-5 py-3">{{ $sale->customer?->name ?? 'Walk-in' }}@if ($sale->price_tier?->value === 'wholesale') <span class="ml-1 rounded bg-amber-100 px-1.5 py-px text-[10.5px] font-bold text-amber-800">Wholesale</span>@endif</td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap tabular-nums">{{ $sale->money('total') }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums text-slate-600">{{ $sale->money('paid') }}</td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap tabular-nums {{ $sale->balancePesewas() > 0 ? 'text-amber-700' : 'text-slate-400' }}">{{ $sale->money('balance') }}</td>
                            <td class="px-5 py-3"><x-sale-status :status="$sale->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">No sales for these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $sales->links() }}</div>
        @endif
    </div>
</div>
