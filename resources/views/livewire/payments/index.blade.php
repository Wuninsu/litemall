<div class="grid gap-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" id="payment-search" placeholder="Customer or reference" aria-label="Search payments"
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
        </div>

        <select wire:model.live="period" id="payment-period-filter" aria-label="Period"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="today">Today</option>
            <option value="7days">Last 7 days</option>
            <option value="30days">Last 30 days</option>
            <option value="">All time</option>
        </select>

        <select wire:model.live="method" id="payment-method-filter" aria-label="Method"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">Any method</option>
            @foreach ($methods as $methodOption)
                <option value="{{ $methodOption->value }}">{{ $methodOption->label() }}</option>
            @endforeach
        </select>

        <x-button icon="wallet" wire:click="openPanel" class="sm:ml-auto">Receive payment</x-button>
    </div>

    @if ($notice)
        <div wire:key="notice-{{ md5($notice) }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition.opacity
             class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">
            <x-icon name="check" class="size-4" />
            {{ $notice }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4">
        <p class="text-[13px] font-semibold text-slate-500">Received in this period</p>
        <p class="mt-1 text-xl font-extrabold tabular-nums">{{ App\Support\Money::format($periodTotal) }}</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11.5px] font-bold tracking-wider text-slate-400 uppercase">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Applied to</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr wire:key="payment-{{ $payment->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <p>{{ $payment->received_at->format('j M Y, g:i a') }}</p>
                                <p class="text-xs text-slate-500">by {{ $payment->receiver->name }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $payment->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-5 py-3">
                                <p>{{ $payment->method->label() }}</p>
                                @if ($payment->reference)
                                    <p class="font-mono text-xs text-slate-500">{{ $payment->reference }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($payment->allocations as $allocation)
                                        <a href="{{ route('sales.show', $allocation->sale) }}" wire:navigate
                                           class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700 hover:bg-teal-50 hover:text-teal-800"
                                           title="{{ $allocation->formattedAmount() }}">{{ $allocation->sale->receipt_number }}</a>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap tabular-nums">{{ $payment->formattedAmount() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No payments for these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $payments->links() }}</div>
        @endif
    </div>

    {{-- Receive payment panel --}}
    <x-modal wire:model="showModal" position="right" max-width="max-w-xl" title="Receive payment" subtitle="Applied to the customer's oldest unpaid sales first.">
        <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
            <div class="panel-scroll grid min-h-0 flex-1 content-start gap-8 overflow-y-auto px-6 py-6">
                <section class="grid gap-3">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Customer</h3>

                    @if ($this->customer)
                        <div class="flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50/60 p-3">
                            <div class="min-w-0 flex-1 text-sm">
                                <p class="font-bold">{{ $this->customer->name }}</p>
                                <p class="text-[13px] text-slate-600">{{ $this->customer->formattedPhone() }}</p>
                                <p class="mt-1 text-[13px] font-semibold {{ $this->customer->outstandingPesewas() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    Owes {{ App\Support\Money::format($this->customer->outstandingPesewas()) }}
                                </p>
                            </div>
                            <x-button variant="subtle" size="sm" wire:click="clearCustomer" :loading="false">Change</x-button>
                        </div>
                    @else
                        <input wire:model.live.debounce.250ms="customerSearch" id="payment-customer-search" type="search" autocomplete="off" placeholder="Search customers who owe money"
                               aria-label="Search customers"
                               class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                        <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                            @forelse ($this->customerResults as $result)
                                <li wire:key="payer-{{ $result->id }}">
                                    <button type="button" wire:click="selectCustomer('{{ $result->id }}')" class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-teal-50/60">
                                        <span>
                                            <span class="block font-semibold">{{ $result->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $result->formattedPhone() }}</span>
                                        </span>
                                        <span class="text-sm font-bold text-amber-700 tabular-nums">{{ App\Support\Money::format($result->outstandingPesewas()) }}</span>
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-4 text-center text-sm text-slate-500">No customers with an outstanding balance{{ $customerSearch !== '' ? ' match “'.$customerSearch.'”' : '' }}.</li>
                            @endforelse
                        </ul>
                    @endif
                    @error('customerId') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                </section>

                <section class="grid gap-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Payment</h3>
                        @if ($this->customer && $this->customer->outstandingPesewas() > 0)
                            <x-button variant="secondary" size="sm" wire:click="payFullBalance" :loading="false">Full balance</x-button>
                        @endif
                    </div>
                    <x-input wire:model.live.debounce.400ms="amount" id="payment-amount" label="Amount received" prefix="GH₵" placeholder="0.00" inputmode="decimal" autocomplete="off" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label for="payment-method" class="text-[13px] font-semibold text-slate-700">Method</label>
                            <select wire:model.live="paymentMethod" id="payment-method" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
                                @foreach ($methods as $methodOption)
                                    <option value="{{ $methodOption->value }}">{{ $methodOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-input wire:model="reference" id="payment-reference" label="Reference (optional)" placeholder="e.g. MoMo transaction ID" />
                    </div>
                    <x-textarea wire:model="notes" id="payment-notes" label="Notes (optional)" rows="2" />
                </section>

                @if ($this->allocationPreview !== [])
                    <section class="grid gap-3">
                        <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Will be applied to</h3>
                        <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 text-sm">
                            @foreach ($this->allocationPreview as ['sale' => $previewSale, 'amount' => $previewAmount])
                                <li wire:key="preview-{{ $previewSale->id }}" class="flex items-center justify-between gap-3 px-3 py-2.5">
                                    <span>
                                        <span class="font-mono font-semibold">{{ $previewSale->receipt_number }}</span>
                                        <span class="block text-xs text-slate-500">{{ $previewSale->sold_at->format('j M Y') }} · owes {{ $previewSale->money('balance') }}</span>
                                    </span>
                                    <span class="text-right">
                                        <span class="block font-semibold tabular-nums">{{ App\Support\Money::format($previewAmount) }}</span>
                                        <span class="block text-xs {{ $previewAmount >= $previewSale->balancePesewas() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $previewAmount >= $previewSale->balancePesewas() ? 'Settled' : 'Part-paid' }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <x-button variant="ghost" wire:click="closeModal" :loading="false">Cancel</x-button>
                <x-button type="submit" target="save" loading-text="Saving…">Record payment</x-button>
            </div>
        </form>
    </x-modal>
</div>
