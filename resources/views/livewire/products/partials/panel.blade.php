{{--
    Product create / edit panel. Used by Products\Index and Products\Show (trait ManagesProductPanel).
    Expects: $form (ProductForm), $categories (list<string>). Render only for users who can manage products.
--}}
@php
    $editing = $form->product;
@endphp

<x-modal wire:model="showModal" position="right" max-width="max-w-xl"
         :title="$editing ? 'Edit product' : 'New product'"
         :subtitle="$editing ? $editing->name.' · currently '.$editing->formattedPrice() : 'Name and price are required.'">
    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <div class="panel-scroll grid min-h-0 flex-1 content-start gap-6 overflow-y-auto px-6 py-6">
            {{-- Photo: the whole area is the file picker (click or drop an image on it) --}}
            @php
                $previewUrl = null;
                if ($form->image && method_exists($form->image, 'isPreviewable') && $form->image->isPreviewable()) {
                    $previewUrl = $form->image->temporaryUrl();
                } elseif ($editing && ! $form->removeImage) {
                    $previewUrl = $editing->imageUrl();
                }
            @endphp

            <div class="grid gap-1.5">
                <div @class([
                    'group relative h-40 w-full overflow-hidden rounded-2xl transition',
                    'bg-slate-100' => $previewUrl,
                    'border-2 border-dashed border-slate-300 bg-slate-50 hover:border-teal-500 hover:bg-teal-50/50' => ! $previewUrl,
                ])>
                    @if ($previewUrl)
                        <img src="{{ $previewUrl }}" alt="Product photo" class="size-full object-cover">
                    @else
                        <div class="pointer-events-none grid size-full place-items-center text-center">
                            <div class="grid justify-items-center gap-2 px-6">
                                <span class="grid size-12 place-items-center rounded-full bg-white text-teal-700 shadow-sm ring-1 ring-slate-200"><x-icon name="plus" class="size-6" /></span>
                                <p class="text-sm font-bold text-slate-700">Click or drop a photo</p>
                                <p class="text-[12.5px] text-slate-500">JPG, PNG or WebP, up to 2 MB</p>
                            </div>
                        </div>
                    @endif

                    {{-- Invisible file input covering the whole area: handles both click and drag-and-drop --}}
                    <input wire:model="form.image" id="product-image" type="file" accept="image/jpeg,image/png,image/webp"
                           aria-label="{{ $previewUrl ? 'Change product photo' : 'Upload product photo' }}"
                           class="absolute inset-0 z-10 size-full cursor-pointer opacity-0">

                    @if ($previewUrl)
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex items-center justify-between gap-2 bg-gradient-to-t from-black/70 to-transparent px-4 pt-10 pb-3">
                            <label for="product-image" class="pointer-events-auto inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-white/90 px-3 py-1.5 text-[13px] font-bold text-slate-800 shadow hover:bg-white">
                                <x-icon name="edit" class="size-4" /> Change photo
                            </label>
                            @if ($form->image)
                                <button type="button" wire:click="$set('form.image', null)" class="pointer-events-auto rounded-lg bg-black/40 px-3 py-1.5 text-[13px] font-bold text-white hover:bg-black/60">Undo</button>
                            @elseif ($editing?->image_path)
                                <button type="button" wire:click="$set('form.removeImage', true)" class="pointer-events-auto rounded-lg bg-red-600/90 px-3 py-1.5 text-[13px] font-bold text-white hover:bg-red-600">Remove</button>
                            @endif
                        </div>
                    @endif

                    <div wire:loading.flex wire:target="form.image" class="absolute inset-0 z-30 items-center justify-center gap-2 bg-white/80 text-sm font-bold text-teal-800">
                        <svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                        Uploading…
                    </div>
                </div>

                @if ($form->removeImage && $editing?->image_path)
                    <p class="flex items-center justify-between rounded-lg bg-red-50 px-3 py-2 text-[13px] text-red-800">
                        The current photo will be removed when you save.
                        <button type="button" wire:click="$set('form.removeImage', false)" class="font-bold underline">Keep it</button>
                    </p>
                @endif
                @error('form.image') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
            </div>

            <x-input wire:model="form.name" id="product-name" label="Name" placeholder="e.g. Samsung 43-inch Smart TV" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input wire:model="form.sku" id="product-sku" label="SKU (optional)" placeholder="e.g. TV-SAM-43" class="font-mono uppercase" />
                <x-input wire:model="form.category" id="product-category" label="Category (optional)" placeholder="e.g. Electronics" list="product-categories" autocomplete="off" />
            </div>
            <datalist id="product-categories">
                @foreach ($categories as $categoryOption)
                    <option value="{{ $categoryOption }}"></option>
                @endforeach
            </datalist>

            <div class="grid gap-4">
                {{-- Two aligned rows: prices side by side, then quantities side by side --}}
                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <x-input wire:model="form.price" id="product-price" label="Retail price" prefix="GH₵" placeholder="0.00" inputmode="decimal" autocomplete="off" required />
                    <x-input wire:model="form.wholesalePrice" id="product-wholesale-price" label="Wholesale price" prefix="GH₵" placeholder="Optional" inputmode="decimal" autocomplete="off" />
                    <x-input wire:model="form.stock" id="product-stock" type="number" min="0" step="1" label="Quantity in stock" inputmode="numeric" required />
                    <x-input wire:model="form.wholesaleMinQuantity" id="product-wholesale-min" type="number" min="1" step="1" label="Wholesale from (units)" inputmode="numeric" />
                </div>
                <p class="text-[13px] text-slate-500">
                    Wholesale price is optional; below the "wholesale from" quantity the retail price is charged.
                    @if ($editing)
                        Price changes only affect new sales — past sales keep the price they were sold at.
                    @else
                        Prices in cedis, e.g. 1200 or 1,200.50.
                    @endif
                </p>
            </div>

            <x-textarea wire:model="form.description" id="product-description" label="Description (optional)" placeholder="Model, size, colour, warranty…" />

            @if ($editing)
                @if ($editing->is_active)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-100 bg-red-50/60 px-4 py-3.5">
                        <div class="text-[13px]">
                            <p class="font-bold text-red-900">Deactivate product</p>
                            <p class="text-red-800/80">Hides it from new sales. Past sales are kept.</p>
                        </div>
                        <x-button variant="danger" size="sm" wire:click="deactivate" wire:confirm="Deactivate {{ $editing->name }}?">Deactivate</x-button>
                    </div>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                        <div class="text-[13px]">
                            <p class="font-bold">This product is inactive</p>
                            <p class="text-slate-500">Reactivate to sell it again.</p>
                        </div>
                        <x-button variant="ghost" size="sm" wire:click="activate">Reactivate</x-button>
                    </div>
                @endif
            @endif
        </div>

        <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <x-button variant="ghost" wire:click="closeModal" :loading="false">Cancel</x-button>
            <x-button type="submit" target="save, form.image" :loading-text="$editing ? 'Saving…' : 'Adding…'">{{ $editing ? 'Save changes' : 'Add product' }}</x-button>
        </div>
    </form>
</x-modal>
