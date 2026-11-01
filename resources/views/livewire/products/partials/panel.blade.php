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
        <div class="grid flex-1 content-start gap-8 overflow-y-auto px-6 py-6">
            {{-- Photo --}}
            <section class="grid gap-3">
                <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Photo</h3>

                @php
                    $previewUrl = null;
                    if ($form->image && method_exists($form->image, 'isPreviewable') && $form->image->isPreviewable()) {
                        $previewUrl = $form->image->temporaryUrl();
                    } elseif ($editing && ! $form->removeImage) {
                        $previewUrl = $editing->imageUrl();
                    }
                @endphp

                <div class="flex items-center gap-4">
                    <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 text-slate-300">
                        @if ($previewUrl)
                            <img src="{{ $previewUrl }}" alt="" class="size-full object-cover">
                        @else
                            <x-icon name="box" class="size-8" />
                        @endif
                    </div>

                    <div class="grid gap-2">
                        <label for="product-image" class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[13px] font-bold text-slate-700 hover:bg-slate-50 focus-within:ring-4 focus-within:ring-teal-600/15">
                            <x-icon name="plus" class="size-4" />
                            {{ $previewUrl ? 'Change photo' : 'Upload photo' }}
                            <input wire:model="form.image" id="product-image" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                        <p class="text-[12.5px] text-slate-500">JPG, PNG or WebP, up to 2 MB.</p>
                        <p wire:loading wire:target="form.image" class="text-[12.5px] font-semibold text-teal-700">Uploading…</p>

                        @if ($editing?->image_path && ! $form->image)
                            <label class="flex items-center gap-2 text-[13px] text-slate-600">
                                <input wire:model.live="form.removeImage" type="checkbox" class="size-4 rounded border-slate-300 text-red-600 focus:ring-red-500/30">
                                Remove current photo
                            </label>
                        @endif
                    </div>
                </div>
                @error('form.image') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="grid gap-4">
                <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Product</h3>
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
            </section>

            <section class="grid gap-4">
                <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Price and stock</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input wire:model="form.price" id="product-price" label="Selling price" prefix="GH₵" placeholder="0.00" inputmode="decimal" autocomplete="off" required />
                    <x-input wire:model="form.stock" id="product-stock" type="number" min="0" step="1" label="Quantity in stock" inputmode="numeric" required />
                </div>
                <p class="text-[13px] text-slate-500">
                    @if ($editing)
                        Changing the price only affects new sales. Past sales keep the price they were sold at.
                    @else
                        Price in cedis, e.g. 1200 or 1,200.50. Stock is the number of units on hand.
                    @endif
                </p>
            </section>

            <section class="grid gap-4">
                <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Description</h3>
                <x-textarea wire:model="form.description" id="product-description" label="Description (optional)" placeholder="Model, size, colour, warranty…" />
            </section>

            @if ($editing)
                <section class="grid gap-3">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Status</h3>
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
                </section>
            @endif
        </div>

        <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <x-button variant="ghost" wire:click="closeModal" :loading="false">Cancel</x-button>
            <x-button type="submit" target="save, form.image" :loading-text="$editing ? 'Saving…' : 'Adding…'">{{ $editing ? 'Save changes' : 'Add product' }}</x-button>
        </div>
    </form>
</x-modal>
