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
    <body class="min-h-screen bg-brand font-sans text-teal-50 antialiased">
        <div class="relative isolate flex min-h-screen flex-col overflow-hidden">
            {{-- Background: grid texture and two soft glows --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 brand-texture"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-40 -bottom-48 -z-10 size-[36rem] rounded-full bg-[radial-gradient(circle_at_30%_30%,#14b8a6_0%,transparent_62%)] opacity-55"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-56 -left-40 -z-10 size-[30rem] rounded-full bg-[radial-gradient(circle_at_60%_60%,#2dd4bf_0%,transparent_60%)] opacity-25"></div>

            <header class="flex items-center gap-3 px-6 pt-6 text-lg font-extrabold text-white sm:px-10 sm:pt-8">
                <span class="grid size-10 place-items-center rounded-xl bg-teal-400 font-extrabold text-teal-950">{{ mb_substr(config('app.name'), 0, 1) }}</span>
                {{ config('app.name') }}
            </header>

            <main class="flex flex-1 items-center justify-center px-4 py-10">
                <div class="w-full max-w-md rounded-3xl border border-white/15 bg-white/[.07] p-8 shadow-[0_30px_80px_-30px_rgba(0,0,0,.6)] backdrop-blur-xl sm:p-10">
                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-2 rounded-xl border border-teal-300/30 bg-teal-300/10 px-4 py-3 text-sm text-teal-50" role="status">
                            <x-icon name="check" class="mt-0.5 size-4" />
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <footer class="flex flex-col items-center gap-4 px-6 pb-8 text-center">
                <ul class="hidden flex-wrap justify-center gap-x-8 gap-y-2 text-[13px] text-teal-100/80 sm:flex">
                    <li class="flex items-center gap-2"><x-icon name="receipt" class="size-4 text-teal-300" />Record sales and print receipts</li>
                    <li class="flex items-center gap-2"><x-icon name="wallet" class="size-4 text-teal-300" />Track instalment payments in GH₵</li>
                    <li class="flex items-center gap-2"><x-icon name="people" class="size-4 text-teal-300" />See who owes what, at a glance</li>
                </ul>
                <p class="text-xs text-teal-100/50">Staff access only. Accounts are created by your administrator.</p>
            </footer>
        </div>
    </body>
</html>
