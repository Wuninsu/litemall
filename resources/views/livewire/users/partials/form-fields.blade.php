<div>
    <label for="name" class="block text-sm font-medium">Name</label>
    <input wire:model="form.name" id="name" type="text" required
           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
    @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="email" class="block text-sm font-medium">Email</label>
    <input wire:model="form.email" id="email" type="email" required
           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
    @error('form.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="role" class="block text-sm font-medium">Role</label>
    <select wire:model="form.role" id="role"
            class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
        @foreach ($roles as $role)
            <option value="{{ $role->value }}">{{ $role->label() }}</option>
        @endforeach
    </select>
    @error('form.role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="password" class="block text-sm font-medium">
        Password @if ($passwordOptional) <span class="font-normal text-zinc-500">(leave blank to keep current)</span> @endif
    </label>
    <input wire:model="form.password" id="password" type="password" autocomplete="new-password" @unless ($passwordOptional) required @endunless
           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
    @error('form.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
    <input wire:model="form.password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
</div>
