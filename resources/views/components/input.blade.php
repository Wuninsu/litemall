{{--
    Labelled input. `tone="dark"` gives a translucent version for dark/brand backgrounds (auth pages).
--}}
@props(['label', 'id', 'error' => null, 'hint' => null, 'type' => 'text', 'tone' => 'light'])

@php
    $errorKey = $error ?? $attributes->wire('model')->value();
    $hasError = $errors->has($errorKey);
    $dark = $tone === 'dark';
@endphp

<div class="grid gap-1.5">
    <label for="{{ $id }}" @class(['text-[13px] font-semibold', 'text-slate-700' => ! $dark, 'text-teal-50' => $dark])>{{ $label }}</label>
    <input id="{{ $id }}" type="{{ $type }}"
        {{ $attributes->class([
            'h-11 w-full rounded-xl border px-3.5 text-sm transition focus:outline-none focus:ring-4',
            'bg-white text-slate-900 placeholder:text-slate-400' => ! $dark,
            'border-red-300 focus:border-red-400 focus:ring-red-100' => ! $dark && $hasError,
            'border-slate-300 focus:border-teal-600 focus:ring-teal-600/15' => ! $dark && ! $hasError,
            'bg-white/10 text-white placeholder:text-teal-100/50 focus:bg-white/15' => $dark,
            'border-red-300/70 focus:border-red-300 focus:ring-red-300/20' => $dark && $hasError,
            'border-white/20 focus:border-teal-300 focus:ring-teal-300/20' => $dark && ! $hasError,
        ]) }}
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @error($errorKey)
        <p id="{{ $id }}-error" @class(['text-[13px]', 'text-red-600' => ! $dark, 'text-red-200' => $dark])>{{ $message }}</p>
    @elseif ($hint)
        <p @class(['text-[13px]', 'text-slate-500' => ! $dark, 'text-teal-100/70' => $dark])>{{ $hint }}</p>
    @enderror
</div>
