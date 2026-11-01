{{--
    Reusable button / link.

    Props:
      variant      primary | secondary | ghost | danger | subtle | bright (default primary; bright = for dark backgrounds)
      size         sm | md | lg | icon                                   (default md; icon = square)
      type         button | submit | reset                               (default button; ignored for links)
      href         render an <a> instead of a <button>
      navigate     add wire:navigate to a link                           (default true when href is set)
      icon         leading <x-icon> name
      iconRight    trailing <x-icon> name
      block        full width
      target       Livewire action(s) that show the loader; defaults to the wire:click value,
                   or to "any request" for submit buttons
      loadingText  label shown while loading (otherwise the label stays and a spinner appears)
      loading      false to switch the loader off

    Examples:
      <x-button icon="plus" wire:click="create">New user</x-button>
      <x-button type="submit" target="save" loading-text="Saving…">Save changes</x-button>
      <x-button variant="danger" size="sm" wire:click="deactivate" wire:confirm="Sure?">Deactivate</x-button>
      <x-button variant="subtle" size="icon" icon="edit" wire:click="edit('{{ $id }}')" aria-label="Edit" />
      <x-button variant="secondary" :href="route('users.index')">Back</x-button>
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'navigate' => true,
    'icon' => null,
    'iconRight' => null,
    'block' => false,
    'target' => null,
    'loadingText' => null,
    'loading' => true,
])

@php
    $variants = [
        'primary' => 'bg-teal-700 text-white shadow-sm hover:bg-teal-800 focus-visible:ring-teal-600/30',
        'secondary' => 'bg-teal-50 text-teal-800 hover:bg-teal-100 focus-visible:ring-teal-600/20',
        'ghost' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400/30',
        'danger' => 'border border-red-200 bg-white text-red-700 hover:bg-red-50 focus-visible:ring-red-500/30',
        'subtle' => 'text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus-visible:ring-slate-400/30',
        'bright' => 'bg-teal-300 text-teal-950 shadow-sm hover:bg-teal-200 focus-visible:ring-teal-200/40',
    ];

    $sizes = [
        'sm' => 'h-9 gap-1.5 px-3 text-[13px]',
        'md' => 'h-10 gap-2 px-4 text-sm',
        'lg' => 'h-11 gap-2 px-5 text-sm',
        'icon' => 'size-9',
    ];

    $iconSize = $size === 'sm' ? 'size-4' : ($size === 'icon' ? 'size-[18px]' : 'size-4');

    $isLink = $href !== null;
    $clickAction = $attributes->wire('click')->value();
    $showsLoader = $loading && ! $isLink && ($target !== null || $clickAction || $type === 'submit');
    $wireTarget = $target ?? ($clickAction ?: null);
    $targetAttribute = $wireTarget ? 'wire:target="'.e($wireTarget).'"' : '';

    $classes = [
        'relative inline-flex shrink-0 items-center justify-center rounded-xl font-bold whitespace-nowrap transition select-none',
        'focus:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-60 data-loading:cursor-wait',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        'w-full' => $block,
    ];
@endphp

@if ($isLink)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" :class="$iconSize" /> @endif
        @if ($slot->isNotEmpty()) <span>{{ $slot }}</span> @endif
        @if ($iconRight) <x-icon :name="$iconRight" :class="$iconSize" /> @endif
    </a>
@else
    <button type="{{ $type }}"
        @if ($showsLoader) wire:loading.attr="disabled" {!! $targetAttribute !!} @endif
        {{ $attributes->class($classes) }}>
        @if ($showsLoader)
            <svg wire:loading {!! $targetAttribute !!} class="{{ $iconSize }} animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            <span wire:loading {!! $targetAttribute !!} class="sr-only">Loading</span>
        @endif

        @if ($icon && $showsLoader)
            <span wire:loading.remove {!! $targetAttribute !!} class="contents"><x-icon :name="$icon" :class="$iconSize" /></span>
        @elseif ($icon)
            <x-icon :name="$icon" :class="$iconSize" />
        @endif

        @if ($slot->isNotEmpty())
            @if ($showsLoader && $loadingText)
                <span wire:loading.remove {!! $targetAttribute !!}>{{ $slot }}</span>
                <span wire:loading {!! $targetAttribute !!}>{{ $loadingText }}</span>
            @else
                <span>{{ $slot }}</span>
            @endif
        @endif

        @if ($iconRight)
            <x-icon :name="$iconRight" :class="$iconSize" />
        @endif
    </button>
@endif
