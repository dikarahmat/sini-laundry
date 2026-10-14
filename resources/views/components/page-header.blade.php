@props([
    'title',
    'description' => null,
])

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-semibold text-ink-900 md:text-[28px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-ink-600">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
