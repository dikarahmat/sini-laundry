@props([
    'icon' => 'archive-box',
    'title',
    'description' => null,
])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-300 bg-white px-6 py-12 text-center">
    <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
        <flux:icon :icon="$icon" class="size-7" />
    </div>

    <p class="font-display text-lg font-semibold text-ink-900">{{ $title }}</p>

    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-600">{{ $description }}</p>
    @endif

    @if (isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
