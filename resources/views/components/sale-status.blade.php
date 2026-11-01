{{-- Payment-status pill for a sale: Paid (green), Part-paid (amber), Unpaid (red). --}}
@props(['status'])

@php
    $styles = [
        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'partly_paid' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'unpaid' => 'bg-red-50 text-red-700 ring-red-600/15',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap ring-1 ring-inset', $styles[$status->value]]) }}>{{ $status->label() }}</span>
