<div class="grid gap-5">
    @php($canManage = auth()->user()->can('create', App\Models\Product::class))

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" id="product-search" placeholder="Search name or SKU" aria-label="Search products"
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
        </div>

        <select wire:model.live="category" id="product-category-filter" aria-label="Filter by category"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">All categories</option>
            @foreach ($categories as $categoryOption)
                <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
            @endforeach
        </select>

        <select wire:model.live="stock" id="product-stock-filter" aria-label="Filter by stock"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">Any stock</option>
            <option value="low">Low stock</option>
            <option value="out">Out of stock</option>
        </select>

        <select wire:model.live="status" id="product-status-filter" aria-label="Filter by status"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="">All products</option>
        </select>

        @if ($canManage)
            <x-button icon="plus" wire:click="create" class="sm:ml-auto">New product</x-button>
        @else
            <p class="text-[13px] text-slate-500 sm:ml-auto">Prices and stock are set by an administrator.</p>
        @endif
    </div>

    @if ($notice)
        <div wire:key="notice-{{ md5($notice) }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition.opacity
             class="flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">
            <x-icon name="check" class="size-4" />
            {{ $notice }}
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11.5px] font-bold tracking-wider text-slate-400 uppercase">
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-right">Price</th>
                        <th class="px-5 py-3">Stock</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('products.show', $product) }}" wire:navigate class="group flex items-center gap-3">
                                    <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
                                        @if ($product->imageUrl())
                                            <img src="{{ $product->imageUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                        @else
                                            <x-icon name="box" class="size-5" />
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-semibold group-hover:text-teal-700 group-hover:underline">{{ $product->name }}</span>
                                        @if ($product->sku)
                                            <span class="block font-mono text-xs text-slate-500">{{ $product->sku }}</span>
                                        @endif
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $product->category ?? '—' }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">
                                <span class="font-semibold">{{ $product->formattedPrice() }}</span>
                                @if ($product->hasWholesalePrice())
                                    <span class="block text-xs font-semibold text-amber-700">{{ $product->formattedWholesalePrice() }} wholesale{{ $product->wholesale_min_quantity > 1 ? ' · '.$product->wholesale_min_quantity.'+' : '' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3"><x-stock-badge :product="$product" /></td>
                            <td class="px-5 py-3">
                                @if ($product->is_active)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-current"></span>Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-400"><span class="size-1.5 rounded-full bg-current"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <x-button variant="subtle" size="icon" icon="eye" :href="route('products.show', $product)" aria-label="View {{ $product->name }}" title="View details" />
                                @if ($canManage)
                                    <x-button variant="subtle" size="icon" icon="edit" wire:click="edit('{{ $product->id }}')" aria-label="Edit {{ $product->name }}" title="Edit" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                @if ($search !== '' || $category !== '' || $status !== 'active' || $stock !== '')
                                    No products match these filters.
                                @else
                                    No products yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $products->links() }}</div>
        @endif
    </div>

    @if ($canManage)
        @include('livewire.products.partials.panel')
    @endif
</div>
