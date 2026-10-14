<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface">
        @php
            $user = auth()->user();
            $isCustomerArea = request()->routeIs('pesanan*');
            $cekStatusUrl = Route::has('cek-status') ? route('cek-status') : url('/cek-status');
        @endphp

        <div class="min-h-screen">
            {{-- Header desktop --}}
            <header class="hidden border-b border-ink-300 bg-white md:block">
                <div class="mx-auto flex h-16 max-w-5xl items-center gap-4 px-4">
                    <x-app-logo :href="route('home')" wire:navigate />

                    <nav class="flex items-center gap-1 text-sm font-medium text-ink-600">
                        <a href="{{ route('pesanan.index') }}" wire:navigate
                            class="rounded-xl px-3 py-2 transition hover:bg-primary-50 hover:text-primary-700 {{ $isCustomerArea ? 'bg-primary-50 text-primary-700' : '' }}">
                            Beranda
                        </a>
                        <a href="{{ $cekStatusUrl }}" wire:navigate
                            class="rounded-xl px-3 py-2 transition hover:bg-primary-50 hover:text-primary-700 {{ request()->routeIs('cek-status') ? 'bg-primary-50 text-primary-700' : '' }}">
                            Cek Status
                        </a>
                    </nav>

                    <div class="flex-1"></div>

                    <x-desktop-user-menu class="hidden lg:block" :name="$user?->name" />
                </div>
            </header>

            {{-- Header HP --}}
            <header class="flex h-14 items-center justify-between border-b border-ink-300 bg-white px-4 md:hidden">
                <x-app-logo :href="route('home')" wire:navigate />

                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="$user?->initials()" icon-trailing="chevron-down" />

                    <flux:menu>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="$user?->name" :initials="$user?->initials()" />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ $user?->name }}</flux:heading>
                                <flux:text class="truncate">{{ $user?->email }}</flux:text>
                            </div>
                        </div>
                        <flux:menu.separator />
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Pengaturan Akun
                        </flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer" data-test="logout-button">
                                Keluar
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </header>

            <main class="mx-auto w-full max-w-5xl px-4 pb-24 pt-6 md:pb-10">
                {{ $slot }}
            </main>

            {{-- Bottom navigation HP --}}
            <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-ink-300 bg-white md:hidden"
                aria-label="Navigasi utama">
                <div class="mx-auto grid max-w-lg grid-cols-4">
                    <a href="{{ route('pesanan.index') }}" wire:navigate
                        class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ $isCustomerArea ? 'text-primary-600' : 'text-ink-600' }}">
                        <flux:icon icon="home" class="size-5" variant="outline" />
                        Beranda
                    </a>
                    <a href="{{ route('pesanan.index') }}" wire:navigate
                        class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ request()->routeIs('pesanan.index') ? 'text-primary-600' : 'text-ink-600' }}">
                        <flux:icon icon="archive-box" class="size-5" variant="outline" />
                        Pesanan
                    </a>
                    <a href="{{ $cekStatusUrl }}" wire:navigate
                        class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ request()->routeIs('cek-status') ? 'text-primary-600' : 'text-ink-600' }}">
                        <flux:icon icon="magnifying-glass" class="size-5" variant="outline" />
                        Cek Status
                    </a>
                    <a href="{{ route('profile.edit') }}" wire:navigate
                        class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ request()->routeIs('profile.edit') ? 'text-primary-600' : 'text-ink-600' }}">
                        <flux:icon icon="cog" class="size-5" variant="outline" />
                        Akun
                    </a>
                </div>
            </nav>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
