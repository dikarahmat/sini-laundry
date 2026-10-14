@props([
    'amount',
    'class' => null,
])

@php
    $value = \App\Support\Money::rupiah((int) $amount);
    $classes = trim('tabular-nums '.($class ?? ''));
@endphp

<span @if ($classes !== '') class="{{ $classes }}" @endif data-amount="{{ (int) $amount }}">{{ $value }}</span>
