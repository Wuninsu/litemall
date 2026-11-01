<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased">
        <header class="border-b border-zinc-200 bg-white">
            <nav class="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3">
                <a href="{{ route('dashboard') }}" class="font-semibold" wire:navigate>{{ config('app.name') }}</a>

                <a href="{{ route('dashboard') }}" wire:navigate @class(['text-sm', 'font-medium text-zinc-900' => request()->routeIs('dashboard'), 'text-zinc-600 hover:text-zinc-900' => ! request()->routeIs('dashboard')])>Dashboard</a>

                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" wire:navigate @class(['text-sm', 'font-medium text-zinc-900' => request()->routeIs('users.*'), 'text-zinc-600 hover:text-zinc-900' => ! request()->routeIs('users.*')])>Users</a>
                @endcan

                <div class="ml-auto flex items-center gap-4 text-sm">
                    <span class="text-zinc-600">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-zinc-600 hover:text-zinc-900">Sign out</button>
                    </form>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <p class="mb-6 rounded bg-green-50 px-3 py-2 text-sm text-green-800" role="status">{{ session('status') }}</p>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
