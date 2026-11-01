{{-- Sidebar item, styled for the dark brand sidebar in layouts::app. --}}
@props(['href' => null, 'icon', 'active' => false, 'soon' => false])

@if ($soon)
    <span class="flex cursor-default items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-teal-100/35" title="Coming soon">
        <x-icon :name="$icon" />
        {{ $slot }}
        <span class="ml-auto rounded-full bg-white/[.06] px-2 py-0.5 text-[11px] font-semibold text-teal-100/50">Soon</span>
    </span>
@else
    <a href="{{ $href }}" wire:navigate @if ($active) aria-current="page" @endif
       @class([
           'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300/50',
           'bg-white/10 text-white before:absolute before:inset-y-2 before:-left-4 before:w-1 before:rounded-r-full before:bg-teal-300' => $active,
           'text-teal-100/75 hover:bg-white/[.06] hover:text-white' => ! $active,
       ])>
        <x-icon :name="$icon" @class(['text-teal-300' => $active]) />
        {{ $slot }}
    </a>
@endif
