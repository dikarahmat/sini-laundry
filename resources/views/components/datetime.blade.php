@props([
    'value' => null,
])

@php
    $formatted = \App\Support\Money::datetime($value);
@endphp

<span {{ $attributes->class(['tabular-nums']) }}>{{ $formatted }}</span>
