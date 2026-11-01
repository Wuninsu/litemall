{{-- Stock level pill for a product: In stock / Low stock / Out of stock, with the quantity. --}}
@props(['product'])

@php
    $level = $product->stockLevel();
    $styles = [
        'in' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'low' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'out' => 'bg-red-50 text-red-700 ring-red-600/15',
    ];
    $labels = ['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap ring-1 ring-inset', $styles[$level]]) }}>
    <span class="tabular-nums">{{ number_format($product->stock_quantity) }}</span>
    <span class="font-semibold">· {{ $labels[$level] }}</span>
</span>
