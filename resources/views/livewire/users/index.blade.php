<div class="grid gap-5">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input wire:model.live.debounce.300ms="search" type="search" id="user-search" placeholder="Search name or email" aria-label="Search users"
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
        </div>

        <select wire:model.live="role" id="user-role-filter" aria-label="Filter by role"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">All roles</option>
            @foreach ($roles as $roleOption)
                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" id="user-status-filter" aria-label="Filter by status"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 focus:border-teal-600 focus:ring-4 focus:ring-teal-600/15 focus:outline-none">
            <option value="">Any status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <x-button icon="plus" wire:click="create" class="sm:ml-auto">New user</x-button>
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
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Last sign-in</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span @class(['grid size-9 shrink-0 place-items-center rounded-full text-xs font-extrabold', 'bg-teal-100 text-teal-800' => $user->is_active, 'bg-slate-100 text-slate-500' => ! $user->is_active])>{{ $user->initials() }}</span>
                                    <div class="min-w-0">
                                        <p class="font-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<span class="ml-1 text-xs font-medium text-slate-400">(you)</span>@endif</p>
                                        <p class="truncate text-[13px] text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $user->role->label() }}</td>
                            <td class="px-5 py-3">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-current"></span>Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-400"><span class="size-1.5 rounded-full bg-current"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-button variant="subtle" size="icon" icon="edit" wire:click="edit('{{ $user->id }}')" aria-label="Edit {{ $user->name }}" title="Edit" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                No users match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Shared create / edit panel --}}
    @php($editing = $form->user)
    @php($editingSelf = $editing?->is(auth()->user()) ?? false)

    <x-modal wire:model="showModal" position="right" max-width="max-w-xl"
             :title="$editing ? 'Edit user' : 'New user'"
             :subtitle="$editing ? $editing->name.' · last signed in '.($editing->last_login_at?->diffForHumans() ?? 'never') : 'The new user can sign in straight away.'">
        <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
            <div class="panel-scroll grid min-h-0 flex-1 content-start gap-8 overflow-y-auto px-6 py-6">
                <section class="grid gap-4">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Profile</h3>
                    <x-input wire:model="form.name" id="form-name" label="Full name" placeholder="e.g. Ama Mensah" required />
                    <x-input wire:model="form.email" id="form-email" type="email" label="Email" placeholder="name@example.com" required />
                </section>

                <fieldset class="grid gap-3">
                    <legend class="mb-3 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Role</legend>
                    {{-- One row per role so the list scales as roles are added. --}}
                    <div class="divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200">
                        @foreach ($roles as $roleOption)
                            <label wire:key="role-{{ $roleOption->value }}" @class([
                                'flex items-center gap-3 px-4 py-3 transition has-checked:bg-teal-50/60 has-focus-visible:bg-teal-50',
                                'cursor-pointer hover:bg-slate-50' => ! $editingSelf,
                                'cursor-not-allowed opacity-60' => $editingSelf,
                            ])>
                                <input type="radio" wire:model="form.role" value="{{ $roleOption->value }}" class="size-4 border-slate-300 text-teal-700 focus:ring-0" @disabled($editingSelf)>
                                <span class="grid">
                                    <span class="text-sm font-bold text-slate-900">{{ $roleOption->label() }}</span>
                                    <span class="text-[13px] text-slate-500">{{ $roleOption->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if ($editingSelf)
                        <p class="text-[13px] text-slate-500">You cannot change your own role.</p>
                    @endif
                    @error('form.role') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <section class="grid gap-4">
                    <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">{{ $editing ? 'Change password' : 'Password' }}</h3>
                    <x-input wire:model="form.password" id="form-password" type="password" autocomplete="new-password"
                             :label="$editing ? 'New password' : 'Password'" :placeholder="$editing ? 'Leave blank to keep the current password' : ''"
                             :hint="$editing ? 'Setting a new password signs this user out of other devices.' : null" :required="! $editing" />
                    <x-input wire:model="form.password_confirmation" id="form-password-confirmation" type="password" autocomplete="new-password"
                             label="Confirm password" error="form.password_confirmation" />
                </section>

                @if ($editing && ! $editingSelf)
                    <section class="grid gap-3">
                        <h3 class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Account status</h3>
                        @if ($editing->is_active)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-100 bg-red-50/60 px-4 py-3.5">
                                <div class="text-[13px]">
                                    <p class="font-bold text-red-900">Deactivate account</p>
                                    <p class="text-red-800/80">Signs {{ str($editing->name)->before(' ') }} out everywhere and blocks sign-in. Records are kept.</p>
                                </div>
                                <x-button variant="danger" size="sm" wire:click="deactivate" wire:confirm="Deactivate {{ $editing->name }}?">Deactivate</x-button>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                <div class="text-[13px]">
                                    <p class="font-bold">This account is inactive</p>
                                    <p class="text-slate-500">Reactivate it to allow sign-in again.</p>
                                </div>
                                <x-button variant="ghost" size="sm" wire:click="activate">Reactivate</x-button>
                            </div>
                        @endif
                        @error('form.status') <p class="text-[13px] text-red-600">{{ $message }}</p> @enderror
                    </section>
                @endif
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <x-button variant="ghost" wire:click="closeModal" :loading="false">Cancel</x-button>
                <x-button type="submit" target="save" :loading-text="$editing ? 'Saving…' : 'Creating…'">{{ $editing ? 'Save changes' : 'Create user' }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
