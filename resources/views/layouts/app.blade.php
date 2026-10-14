@php
    $user = auth()->user();
@endphp

@if ($user?->isCustomer())
    <x-layouts::customer :title="$title ?? null">
        {{ $slot }}
    </x-layouts::customer>
@elseif ($user?->isStaff())
    <x-layouts::staff :title="$title ?? null">
        {{ $slot }}
    </x-layouts::staff>
@else
    {{-- Fallback: layout bawaan starter kit (mis. sebelum peran tersedia). --}}
    <x-layouts::app.sidebar :title="$title ?? null">
        <flux:main>
            {{ $slot }}
        </flux:main>
    </x-layouts::app.sidebar>
@endif
