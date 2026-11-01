<div class="grid gap-5">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-80">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" id="customer-search" placeholder="Search name, phone or email" aria-label="Search customers"
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
        </div>

        <select wire:model.live="status" id="customer-status-filter" aria-label="Filter by status"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="owing">Owing money</option>
            <option value="">All customers</option>
        </select>

        @can('create', App\Models\Customer::class)
            <x-button icon="plus" wire:click="create" class="sm:ml-auto">New customer</x-button>
        @endcan
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
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Phone</th>
                        <th class="px-5 py-3">Location</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Added</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <p class="font-semibold">{{ $customer->name }}@if ($customer->is_wholesale) <span class="ml-1 rounded bg-amber-100 px-1.5 py-px text-[10.5px] font-bold text-amber-800">Wholesale</span>@endif</p>
                                @if ($customer->email)
                                    <p class="truncate text-[13px] text-slate-500">{{ $customer->email }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap tabular-nums text-slate-700">{{ $customer->formattedPhone() }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $customer->address ?? '—' }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">
                                @if ($customer->outstandingPesewas() > 0)
                                    <a href="{{ route('payments.index', ['customer' => $customer->id]) }}" wire:navigate class="font-semibold text-amber-700 hover:underline" title="Receive payment">{{ App\Support\Money::format($customer->outstandingPesewas()) }}</a>
                                @else
                                    <span class="text-slate-400">GH₵ 0.00</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($customer->is_active)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-current"></span>Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-400"><span class="size-1.5 rounded-full bg-current"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ $customer->created_at->format('j M Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-button variant="subtle" size="icon" icon="edit" wire:click="edit('{{ $customer->id }}')" aria-label="Edit {{ $customer->name }}" title="Edit" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                @if ($search !== '' || $status !== 'active')
                                    No customers match these filters.
                                @else
                                    No customers yet. Add your first customer to get started.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $customers->links() }}</div>
        @endif
    </div>

    {{-- Shared create / edit panel --}}
    @php($editing = $form->customer)

    <x-modal wire:model="showModal" position="right" max-width="max-w-xl"
             :title="$editing ? 'Edit customer' : 'New customer'"
             :subtitle="$editing ? $editing->name.' · added '.$editing->created_at->format('j M Y') : 'Name and phone number are required.'">
        <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
            <div class="panel-scroll grid min-h-0 flex-1 content-start gap-8 overflow-y-auto px-6 py-6">
                <section class="grid gap-4">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Contact</h3>
                    <x-input wire:model="form.name" id="customer-name" label="Full name" placeholder="e.g. Akua Serwaa" required />
                    <x-input wire:model="form.phone" id="customer-phone" type="tel" label="Phone number" placeholder="024 412 3456" inputmode="tel" autocomplete="off" required
                             hint="Ghana number. Each customer needs a different phone number." />
                    <x-input wire:model="form.email" id="customer-email" type="email" label="Email (optional)" placeholder="name@example.com" />
                </section>

                <section class="grid gap-4">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Details</h3>
                    <x-input wire:model="form.address" id="customer-address" label="Location / address (optional)" placeholder="e.g. Madina, Accra" />
                    <x-textarea wire:model="form.notes" id="customer-notes" label="Notes (optional)" placeholder="Anything staff should know about this customer" />
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm has-checked:border-amber-400 has-checked:bg-amber-50/60">
                        <input wire:model="form.isWholesale" type="checkbox" class="mt-0.5 size-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500/30">
                        <span><span class="block font-bold">Wholesale customer</span><span class="text-[13px] text-slate-500">The POS switches to wholesale prices when this customer is chosen.</span></span>
                    </label>
                </section>

                @if ($editing)
                    @can('changeStatus', $editing)
                        <section class="grid gap-3">
                            <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Status</h3>
                            @if ($editing->is_active)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-100 bg-red-50/60 px-4 py-3.5">
                                    <div class="text-[13px]">
                                        <p class="font-bold text-red-900">Deactivate customer</p>
                                        <p class="text-red-800/80">Hides them from new sales. Their history is kept.</p>
                                    </div>
                                    <x-button variant="danger" size="sm" wire:click="deactivate" wire:confirm="Deactivate {{ $editing->name }}?">Deactivate</x-button>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                    <div class="text-[13px]">
                                        <p class="font-bold">This customer is inactive</p>
                                        <p class="text-slate-500">Reactivate to use them in new sales again.</p>
                                    </div>
                                    <x-button variant="ghost" size="sm" wire:click="activate">Reactivate</x-button>
                                </div>
                            @endif
                        </section>
                    @endcan
                @endif
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <x-button variant="ghost" wire:click="closeModal" :loading="false">Cancel</x-button>
                <x-button type="submit" target="save" :loading-text="$editing ? 'Saving…' : 'Adding…'">{{ $editing ? 'Save changes' : 'Add customer' }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
