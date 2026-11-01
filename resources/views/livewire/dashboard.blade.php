<div class="grid gap-6">
    {{-- Brand banner --}}
    <div class="flex flex-wrap items-end justify-between gap-4 rounded-3xl bg-brand px-6 py-7 text-teal-50 sm:px-8">
        <div>
            <p class="text-sm font-semibold text-teal-300">{{ now()->format('l, j F Y') }}</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $this->greeting() }}, {{ str(auth()->user()->name)->before(' ') }}</h2>
            <p class="mt-2 max-w-xl text-sm text-teal-100/75">Every sale, every cedi, every customer balance in one place.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button variant="bright" icon="plus" :href="route('sales.create')">New sale</x-button>
            <x-button variant="secondary" icon="wallet" :href="route('payments.index', ['receive' => 1])" class="bg-white/10 text-white hover:bg-white/20">Receive payment</x-button>
        </div>
    </div>

    {{-- Money --}}
    <section aria-label="Today" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Sales today', 'value' => App\Support\Money::format($this->moneyStats['salesToday']), 'note' => $this->moneyStats['salesCountToday'].' '.Str::plural('sale', $this->moneyStats['salesCountToday']), 'icon' => 'cart', 'href' => route('sales.index')],
            ['label' => 'Received today', 'value' => App\Support\Money::format($this->moneyStats['receivedToday']), 'note' => 'cash, MoMo, bank and card', 'icon' => 'wallet', 'href' => route('payments.index')],
            ['label' => 'Owed by customers', 'value' => App\Support\Money::format($this->moneyStats['outstanding']), 'note' => $this->moneyStats['owingCustomers'].' '.Str::plural('customer', $this->moneyStats['owingCustomers']).' owing', 'icon' => 'people', 'href' => route('customers.index', ['status' => 'owing'])],
            ['label' => 'Low or out of stock', 'value' => number_format($this->shopStats['lowStock']), 'note' => 'of '.number_format($this->shopStats['products']).' active products', 'icon' => 'box', 'href' => route('products.index', ['stock' => 'low'])],
        ] as $stat)
            <a href="{{ $stat['href'] }}" wire:navigate class="group flex items-start justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-5 transition hover:border-teal-300 hover:shadow-sm">
                <div class="min-w-0">
                    <p class="text-[13px] font-semibold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-1 truncate text-2xl font-extrabold tabular-nums">{{ $stat['value'] }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $stat['note'] }}</p>
                </div>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700 transition group-hover:bg-teal-700 group-hover:text-white"><x-icon :name="$stat['icon']" /></span>
            </a>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
        {{-- Recent sales --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
            <div class="flex items-center justify-between px-6 pt-5 pb-3">
                <h3 class="font-bold">Recent sales</h3>
                <a href="{{ route('sales.index', ['period' => '']) }}" wire:navigate class="text-sm font-semibold text-teal-700 hover:underline">View all</a>
            </div>
            @if ($this->recentSales->isEmpty())
                <p class="px-6 pb-6 text-sm text-slate-500">No sales yet. <a href="{{ route('sales.create') }}" wire:navigate class="font-semibold text-teal-700 underline">Record the first sale</a>.</p>
            @else
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($this->recentSales as $sale)
                            <tr wire:key="recent-{{ $sale->id }}">
                                <td class="px-6 py-2.5">
                                    <a href="{{ route('sales.show', $sale) }}" wire:navigate class="font-mono font-semibold text-teal-800 hover:underline">{{ $sale->receipt_number }}</a>
                                    <p class="text-xs text-slate-500">{{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->sold_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold whitespace-nowrap tabular-nums">{{ $sale->money('total') }}</td>
                                <td class="px-6 py-2.5 text-right"><x-sale-status :status="$sale->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="grid content-start gap-4">
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('customers.index') }}" wire:navigate class="rounded-2xl border border-slate-200/80 bg-white p-5 transition hover:border-teal-300">
                    <p class="text-[13px] font-semibold text-slate-500">Active customers</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ number_format($this->shopStats['customers']) }}</p>
                </a>
                <a href="{{ route('products.index') }}" wire:navigate class="rounded-2xl border border-slate-200/80 bg-white p-5 transition hover:border-teal-300">
                    <p class="text-[13px] font-semibold text-slate-500">Products on sale</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ number_format($this->shopStats['products']) }}</p>
                </a>
            </div>

            @if ($this->teamStats)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold">Team</h3>
                        <a href="{{ route('users.index') }}" wire:navigate class="text-sm font-semibold text-teal-700 hover:underline">Manage</a>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-slate-500">Active staff</dt><dd class="text-lg font-extrabold tabular-nums">{{ $this->teamStats['active'] }}</dd></div>
                        <div><dt class="text-slate-500">Administrators</dt><dd class="text-lg font-extrabold tabular-nums">{{ $this->teamStats['admins'] }}</dd></div>
                        <div><dt class="text-slate-500">Cashiers</dt><dd class="text-lg font-extrabold tabular-nums">{{ $this->teamStats['cashiers'] }}</dd></div>
                        <div><dt class="text-slate-500">Inactive accounts</dt><dd class="text-lg font-extrabold tabular-nums">{{ $this->teamStats['inactive'] }}</dd></div>
                    </dl>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                <h3 class="font-bold">Your account</h3>
                <dl class="mt-3 grid gap-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="font-semibold">{{ auth()->user()->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Role</dt><dd><span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800">{{ auth()->user()->role->label() }}</span></dd></div>
                </dl>
            </div>
        </div>
    </section>
</div>
