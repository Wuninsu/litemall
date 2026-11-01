<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-slate-900 antialiased">
        @php($user = auth()->user())

        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:grid lg:grid-cols-[256px_1fr]">
            {{-- Mobile backdrop --}}
            <div x-show="sidebarOpen" x-transition.opacity x-cloak x-on:click="sidebarOpen = false" class="fixed inset-0 z-30 bg-brand/60 backdrop-blur-sm lg:hidden"></div>

            {{-- Sidebar --}}
            <aside x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 isolate flex w-64 -translate-x-full flex-col overflow-hidden bg-brand px-4 py-5 text-teal-50 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">

                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-2 pb-6 text-lg font-extrabold text-white">
                    <span class="grid size-9 place-items-center rounded-xl bg-teal-400 font-extrabold text-teal-950">{{ mb_substr(config('app.name'), 0, 1) }}</span>
                    {{ config('app.name') }}
                </a>

                <nav class="grid gap-1" aria-label="Main">
                    <x-nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>

                    <p class="px-3 pt-5 pb-1.5 text-[11px] font-bold tracking-wider text-teal-100/40 uppercase">Operations</p>
                    <x-nav-link icon="cart" soon>Sales</x-nav-link>
                    <x-nav-link icon="people" soon>Customers</x-nav-link>
                    <x-nav-link icon="box" soon>Products</x-nav-link>
                    <x-nav-link icon="wallet" soon>Payments</x-nav-link>

                    <p class="px-3 pt-5 pb-1.5 text-[11px] font-bold tracking-wider text-teal-100/40 uppercase">Insights</p>
                    <x-nav-link icon="chart" soon>Reports</x-nav-link>

                    @can('viewAny', App\Models\User::class)
                        <p class="px-3 pt-5 pb-1.5 text-[11px] font-bold tracking-wider text-teal-100/40 uppercase">Admin</p>
                        <x-nav-link :href="route('users.index')" icon="shield" :active="request()->routeIs('users.*')">Users</x-nav-link>
                    @endcan
                </nav>

                <div class="mt-auto flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[.06] p-3 backdrop-blur">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-teal-300 text-[13px] font-extrabold text-teal-950">{{ $user->initials() }}</span>
                    <div class="min-w-0 text-[13px]">
                        <p class="truncate font-bold text-white">{{ $user->name }}</p>
                        <p class="text-teal-100/60">{{ $user->role->label() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                        @csrf
                        <button type="submit" class="rounded-lg p-1.5 text-teal-100/60 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300/50" title="Sign out" aria-label="Sign out">
                            <x-icon name="logout" />
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main column --}}
            <div class="flex min-w-0 flex-col">
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur sm:px-6">
                    <button type="button" x-on:click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                        <x-icon name="menu" />
                    </button>
                    <p class="text-base font-bold">{{ $title ?? config('app.name') }}</p>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">
                            <x-icon name="check" class="mt-0.5 size-4" />
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
