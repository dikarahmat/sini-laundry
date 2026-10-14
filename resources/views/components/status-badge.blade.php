@props([
    'status',
    'size' => 'md',
])

@php
    $styles = \App\Support\OrderStatusStyles::for($status);
    $sizeClasses = $size === 'sm'
        ? 'px-2 py-0.5 text-xs'
        : 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full font-medium', $styles['classes'], $sizeClasses]) }}
    data-status="{{ $styles['label'] }}">
    <flux:icon :icon="$styles['icon']" class="size-3.5" variant="mini" />
    <span>{{ $styles['label'] }}</span>
</span>
