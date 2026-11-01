<div class="grid gap-6">
    @php($canManage = auth()->user()->can('update', $product))

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-button variant="subtle" size="sm" icon="arrow-left" :href="route('products.index')">All products</x-button>

        @if ($canManage)
            <x-button icon="edit" wire:click="edit('{{ $product->id }}')">Edit product</x-button>
        @endif
    </div>

    @if ($notice)
        <div wire:key="notice-{{ md5($notice) }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition.opacity
             class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">
            <x-icon name="check" class="size-4" />
            {{ $notice }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_1fr]">
        {{-- Photo --}}
        <div class="aspect-square overflow-hidden rounded-3xl border border-slate-200/80 bg-white">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="size-full object-cover">
            @else
                <div class="grid size-full place-items-center bg-slate-50 text-slate-300">
                    <div class="grid justify-items-center gap-2">
                        <x-icon name="box" class="size-14" />
                        <span class="text-sm font-semibold text-slate-400">No photo yet</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Summary --}}
        <div class="grid content-start gap-6">
            <div class="grid gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($product->category)
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800">{{ $product->category }}</span>
                    @endif
                    @if ($product->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"><span class="size-1.5 rounded-full bg-current"></span>Active</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500"><span class="size-1.5 rounded-full bg-current"></span>Inactive — not available for new sales</span>
                    @endif
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-balance sm:text-3xl">{{ $product->name }}</h2>
                @if ($product->sku)
                    <p class="font-mono text-sm text-slate-500">SKU {{ $product->sku }}</p>
                @endif
            </div>

            <dl class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <dt class="text-[13px] font-semibold text-slate-500">Selling price</dt>
                    <dd class="mt-1 text-2xl font-extrabold tabular-nums">{{ $product->formattedPrice() }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <dt class="text-[13px] font-semibold text-slate-500">In stock</dt>
                    <dd class="mt-1 flex flex-wrap items-baseline gap-2">
                        <span class="text-2xl font-extrabold tabular-nums">{{ number_format($product->stock_quantity) }}</span>
                        <span class="text-sm text-slate-500">{{ Str::plural('unit', $product->stock_quantity) }}</span>
                    </dd>
                    <dd class="mt-2"><x-stock-badge :product="$product" /></dd>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <dt class="text-[13px] font-semibold text-slate-500">Stock value</dt>
                    <dd class="mt-1 text-2xl font-extrabold tabular-nums">{{ App\Support\Money::format($product->stockValuePesewas()) }}</dd>
                    <dd class="mt-1 text-xs text-slate-500">Quantity × current selling price</dd>
                </div>
            </dl>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                <h3 class="font-bold">Description</h3>
                @if ($product->description)
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $product->description }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-400">No description.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6">
                <h3 class="font-bold">Record</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Added by</dt><dd class="font-semibold">{{ $product->creator?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Added on</dt><dd class="font-semibold">{{ $product->created_at->format('j M Y, g:i a') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Last updated</dt><dd class="font-semibold">{{ $product->updated_at->diffForHumans() }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    @if ($canManage)
        @include('livewire.products.partials.panel')
    @endif
</div>
