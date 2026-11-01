<div class="grid gap-6">
    {{-- Brand banner --}}
    <div class="rounded-3xl bg-brand px-6 py-7 text-teal-50 sm:px-8">
        <p class="text-sm font-semibold text-teal-300">{{ now()->format('l, j F Y') }}</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $this->greeting() }}, {{ str(auth()->user()->name)->before(' ') }}</h2>
        <p class="mt-2 max-w-xl text-sm text-teal-100/75">Every sale, every cedi, every customer balance in one place.</p>
    </div>

    <section aria-label="Shop" class="grid gap-4 sm:grid-cols-2">
        @foreach ([
            ['label' => 'Active customers', 'value' => $this->shopStats['customers'], 'icon' => 'people', 'href' => route('customers.index')],
            ['label' => 'Products on sale', 'value' => $this->shopStats['products'], 'icon' => 'box', 'href' => route('products.index')],
        ] as $stat)
            <a href="{{ $stat['href'] }}" wire:navigate class="group flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white p-5 transition hover:border-teal-300 hover:shadow-sm">
                <div>
                    <p class="text-[13px] font-semibold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ number_format($stat['value']) }}</p>
                </div>
                <span class="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-700 transition group-hover:bg-teal-700 group-hover:text-white"><x-icon :name="$stat['icon']" /></span>
            </a>
        @endforeach
    </section>

    @if ($this->teamStats)
        <section aria-label="Team" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Active staff', 'value' => $this->teamStats['active'], 'icon' => 'people'],
                ['label' => 'Administrators', 'value' => $this->teamStats['admins'], 'icon' => 'shield'],
                ['label' => 'Cashiers', 'value' => $this->teamStats['cashiers'], 'icon' => 'receipt'],
                ['label' => 'Inactive accounts', 'value' => $this->teamStats['inactive'], 'icon' => 'clock'],
            ] as $stat)
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white p-5">
                    <div>
                        <p class="text-[13px] font-semibold text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ $stat['value'] }}</p>
                    </div>
                    <span class="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-700"><x-icon :name="$stat['icon']" /></span>
                </div>
            @endforeach
        </section>
    @endif

    <section class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
            <h3 class="font-bold">Coming next</h3>
            <p class="mt-1 text-sm text-slate-500">Sales figures, payments and balances will appear on this dashboard as each module goes live.</p>

            <ul class="mt-5 divide-y divide-slate-100">
                @foreach ([
                    ['icon' => 'cart', 'name' => 'Sales', 'text' => 'Record sales with discounts and print receipts.'],
                    ['icon' => 'wallet', 'name' => 'Payments', 'text' => 'Take instalment payments against outstanding sales.'],
                ] as $module)
                    <li class="flex items-center gap-4 py-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-icon :name="$module['icon']" class="size-[18px]" /></span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold">{{ $module['name'] }}</p>
                            <p class="text-[13px] text-slate-500">{{ $module['text'] }}</p>
                        </div>
                        <span class="ml-auto rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">Soon</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
            <h3 class="font-bold">Your account</h3>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="font-semibold">{{ auth()->user()->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="truncate font-semibold">{{ auth()->user()->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Role</dt><dd><span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800">{{ auth()->user()->role->label() }}</span></dd></div>
            </dl>
        </div>
    </section>
</div>
