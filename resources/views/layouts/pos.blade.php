{{-- Full-screen point-of-sale layout: brand top bar, no sidebar. Used by Sales\Create. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full overflow-hidden bg-canvas font-sans text-slate-900 antialiased">
        <div class="flex h-full flex-col">
            <header class="flex h-14 shrink-0 items-center gap-3 bg-brand px-3 text-teal-50 sm:px-5">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 font-extrabold text-white" title="Back to dashboard">
                    <span class="grid size-8 place-items-center rounded-lg bg-teal-400 text-sm font-extrabold text-teal-950">{{ mb_substr(config('app.name'), 0, 1) }}</span>
                    <span class="hidden sm:inline">{{ config('app.name') }}</span>
                </a>
                <span class="rounded-md bg-white/10 px-2 py-0.5 text-xs font-bold tracking-wider text-teal-200 uppercase">Point of sale</span>

                <div class="ml-auto flex items-center gap-1 sm:gap-2">
                    <span x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000 * 15)"
                          x-text="now.toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })"
                          class="hidden text-sm text-teal-100/80 tabular-nums md:inline"></span>

                    <span class="hidden items-center gap-2 rounded-lg bg-white/[.06] px-2.5 py-1 text-sm lg:flex">
                        <span class="grid size-6 place-items-center rounded-full bg-teal-300 text-[11px] font-extrabold text-teal-950">{{ auth()->user()->initials() }}</span>
                        {{ auth()->user()->name }}
                    </span>

                    <a href="{{ route('sales.index') }}" wire:navigate class="rounded-lg px-2.5 py-1.5 text-sm font-semibold text-teal-100/80 hover:bg-white/10 hover:text-white">Sales</a>
                    <button type="button" x-data x-on:click="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen?.()"
                            class="hidden rounded-lg p-1.5 text-teal-100/80 hover:bg-white/10 hover:text-white sm:block" title="Full screen" aria-label="Toggle full screen">
                        <x-icon name="expand" class="size-5" />
                    </button>
                    <a href="{{ route('dashboard') }}" wire:navigate class="rounded-lg p-1.5 text-teal-100/80 hover:bg-white/10 hover:text-white" title="Exit POS" aria-label="Exit point of sale">
                        <x-icon name="x" class="size-5" />
                    </a>
                </div>
            </header>

            <main class="min-h-0 flex-1">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
