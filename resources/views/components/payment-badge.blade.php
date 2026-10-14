@props([
    'status',
    'size' => 'md',
])

@php
    // Peta warna status pembayaran (memakai token status dari style-guide).
    $map = [
        'unpaid' => ['label' => 'Belum Bayar', 'classes' => 'bg-status-pending-confirmation text-status-pending-confirmation-ink'],
        'partial' => ['label' => 'DP', 'classes' => 'bg-status-ironing text-status-ironing-ink'],
        'paid' => ['label' => 'Lunas', 'classes' => 'bg-status-done text-status-done-ink'],
    ];

    $key = $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    $style = $map[$key] ?? ['label' => $key, 'classes' => 'bg-status-picked-up text-status-picked-up-ink'];
    $sizeClasses = $size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full font-medium', $style['classes'], $sizeClasses]) }}
    data-payment-status="{{ $style['label'] }}">
    {{ $style['label'] }}
</span>
