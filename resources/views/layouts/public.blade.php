<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface">
        <div class="mx-auto flex min-h-screen w-full max-w-[480px] flex-col px-4 py-6">
            <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2" wire:navigate>
                <span class="flex size-9 items-center justify-center rounded-xl bg-primary-600 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                        <path d="M12 2.7c3.3 3.5 5.8 6.7 5.8 9.8a5.8 5.8 0 1 1-11.6 0c0-3.1 2.5-6.3 5.8-9.8Z" />
                        <path d="M9.3 13.6a2.9 2.9 0 0 0 2.2 3.1" stroke-width="1.6" />
                    </svg>
                </span>
                <span class="font-display text-lg font-semibold text-ink-900">{{ config('app.name', 'Sini Laundry') }}</span>
            </a>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mt-8 text-center text-xs text-ink-600">
                {{ config('laundry.business.name') }} · {{ config('laundry.business.phone') }}
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
