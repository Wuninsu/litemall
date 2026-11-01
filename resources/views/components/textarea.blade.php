{{-- Labelled multi-line text field; same styling and error handling as <x-input>. --}}
@props(['label', 'id', 'error' => null, 'hint' => null, 'rows' => 3])

@php
    $errorKey = $error ?? $attributes->wire('model')->value();
    $hasError = $errors->has($errorKey);
@endphp

<div class="grid gap-1.5">
    <label for="{{ $id }}" class="text-[13px] font-semibold text-slate-700">{{ $label }}</label>
    <textarea id="{{ $id }}" rows="{{ $rows }}"
        {{ $attributes->class([
            'w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:ring-4',
            'border-red-300 focus:border-red-400 focus:ring-red-100' => $hasError,
            'border-slate-300 focus:border-teal-600 focus:ring-teal-600/15' => ! $hasError,
        ]) }}
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif></textarea>
    @error($errorKey)
        <p id="{{ $id }}-error" class="text-[13px] text-red-600">{{ $message }}</p>
    @elseif ($hint)
        <p class="text-[13px] text-slate-500">{{ $hint }}</p>
    @enderror
</div>
