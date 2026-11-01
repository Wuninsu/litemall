{{--
    Livewire-driven dialog. `wire:model` names a boolean property on the parent component
    that controls visibility, e.g. <x-modal wire:model="showModal" title="New user">.

    position="center" (default): centred dialog, bottom sheet on phones.
    position="right": full-height panel sliding in from the right edge. The slot should be a
    flex column (e.g. a <form class="flex min-h-0 flex-1 flex-col">) with a scrollable body
    and a footer, so the footer stays pinned to the bottom.
--}}
@props(['title', 'subtitle' => null, 'maxWidth' => 'max-w-lg', 'position' => 'center'])

@php
    $model = $attributes->wire('model')->value();
    $isRight = $position === 'right';
@endphp

<div x-data="{ open: $wire.entangle('{{ $model }}') }"
     x-show="open"
     x-cloak
     x-on:keydown.escape.window="open = false"
     @class([
         'fixed inset-0 z-50 flex',
         'justify-end' => $isRight,
         'items-end justify-center p-4 sm:items-center' => ! $isRight,
     ])
     role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]" x-on:click="open = false"></div>

    @if ($isRight)
        <div x-show="open"
             x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative flex h-dvh w-full {{ $maxWidth }} flex-col bg-white shadow-2xl">
    @else
        <div x-show="open"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="relative w-full {{ $maxWidth }} overflow-hidden rounded-2xl bg-white shadow-2xl">
    @endif
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <h2 id="modal-title" class="text-lg font-extrabold text-slate-900">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-[13px] text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                <x-icon name="x" />
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
