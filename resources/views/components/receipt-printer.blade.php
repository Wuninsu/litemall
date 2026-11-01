{{--
    Animated receipt printer for the POS "Sale complete" screen.

    Stages (Alpine): processing (0.7s) → printing (paper fed out in steps, 1.75s, see `receipt-feed` in app.css)
    → done. With reduced motion the finished receipt is shown straight away. Purely visual: real printing is
    the receipt page (`sales.show`).

    Props: sale (Sale with lines, customer, seller loaded), changeDue (pesewas, cash change to hand back).
--}}
@props(['sale', 'changeDue' => 0])

@php
    // Zig-zag torn edge along the bottom of the paper: 40 teeth, 4px deep.
    $teeth = 40;
    $points = ['0 0', '100% 0', '100% calc(100% - 4px)'];
    for ($i = 1; $i <= $teeth * 2; $i++) {
        $x = round(100 - ($i * 100 / ($teeth * 2)), 3);
        $points[] = $x.'% '.($i % 2 === 1 ? '100%' : 'calc(100% - 4px)');
    }
    $tornEdge = 'polygon('.implode(', ', $points).')';
    $isWholesale = $sale->price_tier?->value === 'wholesale';
@endphp

<section {{ $attributes->class('relative isolate mx-auto flex w-full max-w-sm flex-col items-center') }}
         x-data="{
             stage: 'processing',
             timers: [],
             start() {
                 this.timers.forEach(clearTimeout);
                 if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.stage = 'done'; return; }
                 this.stage = 'processing';
                 this.timers = [
                     setTimeout(() => this.stage = 'printing', 700),
                     setTimeout(() => this.stage = 'done', 700 + 1750),
                 ];
             },
         }"
         x-init="start()"
         aria-label="Receipt {{ $sale->receipt_number }}">

    {{-- Printer body --}}
    <div class="relative z-10 w-full rounded-3xl border border-black/40 bg-brand p-3 pb-8 shadow-[0_20px_36px_-20px_rgba(0,0,0,.55),0_6px_14px_-8px_rgba(0,0,0,.24),inset_0_1px_0_rgba(255,255,255,.12),inset_0_-1px_0_rgba(0,0,0,.5)]">
        {{-- Screen --}}
        <div class="relative overflow-hidden rounded-2xl bg-[#04201f] p-4 text-teal-50 shadow-[inset_0_0_24px_4px_rgba(0,0,0,.45)]">
            <div class="flex items-center gap-2 text-sm font-semibold" role="status" aria-live="polite">
                <span x-show="stage !== 'done'" class="grid size-[18px] place-items-center">
                    <svg class="size-[18px] animate-spin text-teal-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </span>
                <span x-show="stage === 'done'" x-cloak class="grid size-[18px] place-items-center rounded-full bg-emerald-400 text-emerald-950">
                    <x-icon name="check" class="size-3" />
                </span>
                <span x-show="stage === 'processing'">Processing the sale</span>
                <span x-show="stage === 'printing'" x-cloak>Printing the receipt</span>
                <span x-show="stage === 'done'" x-cloak>Sale complete</span>
            </div>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="font-mono text-xs text-teal-200/70">{{ $sale->receipt_number }}</span>
                <span class="text-2xl font-extrabold text-white tabular-nums">{{ $sale->money('total') }}</span>
            </div>
        </div>

        {{-- Paper slot --}}
        <div class="absolute inset-x-6 bottom-3 z-40 h-2 rounded-[0.25rem] border border-black/50 bg-black/70 shadow-inner"></div>
    </div>

    {{-- Paper output: clipped so the receipt appears to come out of the slot --}}
    <div class="relative z-50 -mt-4 w-[calc(80%+3rem)] max-w-full overflow-hidden px-6 pb-4">
        <div class="pointer-events-none absolute inset-x-6 -top-1 h-2 bg-black/60 blur-[6px]"></div>

        <article class="receipt-paper relative font-mono text-[12px] leading-relaxed text-slate-800 drop-shadow-[0_8px_16px_rgba(0,0,0,.25)]"
                 x-bind:data-stage="stage" data-stage="processing">
            <div class="bg-[linear-gradient(180deg,#ffffff_0%,#fbfaf7_100%)] px-6 pt-7 pb-8" style="clip-path: {{ $tornEdge }}">
                <header class="text-center">
                    <p class="font-sans text-sm font-extrabold tracking-wide uppercase">{{ config('app.name') }}</p>
                    <p class="text-slate-500">Sales receipt{{ $isWholesale ? ' · Wholesale' : '' }}</p>
                </header>

                <div class="my-3 border-t border-dashed border-slate-300"></div>

                <dl class="grid gap-0.5">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Receipt</dt><dd class="font-bold">{{ $sale->receipt_number }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Date</dt><dd>{{ $sale->sold_at->format('d M Y · H:i') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Served by</dt><dd class="truncate">{{ $sale->seller->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Customer</dt><dd class="truncate">{{ $sale->customer?->name ?? 'Walk-in' }}</dd></div>
                </dl>

                <div class="my-3 border-t border-dashed border-slate-300"></div>

                <ul class="grid gap-1.5">
                    @foreach ($sale->lines as $line)
                        <li>
                            <div class="flex justify-between gap-3">
                                <span class="truncate font-semibold">{{ $line->product_name }}</span>
                                <span class="shrink-0 font-semibold tabular-nums">{{ App\Support\Money::format($line->line_total_pesewas, withSymbol: false) }}</span>
                            </div>
                            <div class="text-slate-500 tabular-nums">
                                {{ $line->quantity }} × {{ App\Support\Money::format($line->unit_price_pesewas, withSymbol: false) }}{{ $line->price_tier?->value === 'wholesale' ? ' · wholesale' : '' }}
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="my-3 border-t border-dashed border-slate-300"></div>

                <dl class="grid gap-0.5 tabular-nums">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ $sale->money('subtotal') }}</dd></div>
                    @if ($sale->discount_pesewas > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>−{{ $sale->money('discount') }}</dd></div>
                    @endif
                    <div class="mt-1 flex justify-between text-[14px] font-extrabold"><dt>Total</dt><dd>{{ $sale->money('total') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ $sale->money('paid') }}</dd></div>
                    @if ($changeDue > 0)
                        <div class="flex justify-between font-bold"><dt>Change</dt><dd>{{ App\Support\Money::format($changeDue) }}</dd></div>
                    @endif
                    @if ($sale->balancePesewas() > 0)
                        <div class="flex justify-between font-bold"><dt>Balance on credit</dt><dd>{{ $sale->money('balance') }}</dd></div>
                    @endif
                </dl>

                <div class="my-3 border-t border-dashed border-slate-300"></div>

                <p class="text-center text-slate-500">Thank you for your business</p>
            </div>
        </article>

        {{-- Floor shadow under the paper --}}
        <div class="pointer-events-none absolute inset-x-[8%] bottom-1 h-3 rounded-full bg-black/20 blur-lg"></div>
    </div>

    <button type="button" x-on:click="start()" class="mt-1 text-xs font-semibold text-teal-100/70 hover:text-white">Replay</button>
</section>
