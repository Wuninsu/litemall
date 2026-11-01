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
        <main class="flex min-h-screen items-center justify-center px-4">
            <div class="w-full max-w-sm">
                <h1 class="mb-6 text-center text-2xl font-semibold">{{ config('app.name') }}</h1>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-zinc-200">
                    @if (session('status'))
                        <p class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800" role="status">{{ session('status') }}</p>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>
