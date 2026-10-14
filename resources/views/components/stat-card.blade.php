@props([
    'title',
    'value',
    'icon' => null,
    'description' => null,
    'href' => null,
])

@php
    $wrapper = $href ? 'a' : 'div';
@endphp

<{{ $wrapper }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['block rounded-2xl border border-ink-300 bg-white p-5 shadow-sm transition hover:shadow-md']) }}>
    <div class="flex items-start gap-3">
        @if ($icon)
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                <flux:icon :icon="$icon" class="size-5" />
            </div>
        @endif
        <div class="min-w-0">
            <p class="text-sm font-medium text-ink-600">{{ $title }}</p>
            <p class="mt-1 font-display text-2xl font-semibold text-ink-900">{{ $value }}</p>
            @if ($description)
                <p class="mt-1 text-xs text-ink-600">{{ $description }}</p>
            @endif
        </div>
    </div>
</{{ $wrapper }}>
