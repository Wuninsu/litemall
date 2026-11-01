<div>
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold">Users</h1>
        <a href="{{ route('users.create') }}" wire:navigate class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">New user</a>
    </div>

    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or email…" aria-label="Search users"
           class="mb-4 w-full max-w-sm rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-600">
                <tr>
                    <th class="px-4 py-2 font-medium">Name</th>
                    <th class="px-4 py-2 font-medium">Email</th>
                    <th class="px-4 py-2 font-medium">Role</th>
                    <th class="px-4 py-2 font-medium">Status</th>
                    <th class="px-4 py-2 font-medium">Last sign-in</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2">{{ $user->role->label() }}</td>
                        <td class="px-4 py-2">
                            @if ($user->is_active)
                                <span class="rounded bg-green-50 px-2 py-0.5 text-green-800">Active</span>
                            @else
                                <span class="rounded bg-zinc-100 px-2 py-0.5 text-zinc-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-zinc-600">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('users.edit', $user) }}" wire:navigate class="text-zinc-600 underline hover:text-zinc-900">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-zinc-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
