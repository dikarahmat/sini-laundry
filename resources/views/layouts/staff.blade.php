<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface">
        @php
            $user = auth()->user();
            $ownerItemsEnabled = $user?->isOwner() === true;
        @endphp

        {{-- Sidebar desktop --}}
        <flux:sidebar sticky collapsible="mobile" class="border-e border-ink-300 bg-white">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" :href="route('home')" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Kasir" class="grid">
                    @if (Route::has('admin.pesanan.baru'))
                        <flux:sidebar.item icon="plus-circle" :href="route('admin.pesanan.baru')"
                            :current="request()->routeIs('admin.pesanan.baru')" wire:navigate>
                            Pesanan Baru
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item icon="queue-list" :href="route('admin.pesanan.index')"
                        :current="request()->routeIs('admin.pesanan.*')" wire:navigate>
                        Daftar Pesanan
                    </flux:sidebar.item>

                    @if (Route::has('admin.pelanggan.index'))
                        <flux:sidebar.item icon="users" :href="route('admin.pelanggan.index')"
                            :current="request()->routeIs('admin.pelanggan.*')" wire:navigate>
                            Pelanggan
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if ($ownerItemsEnabled)
                    <flux:sidebar.group heading="Pemilik" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('owner.dashboard')"
                            :current="request()->routeIs('owner.dashboard')" wire:navigate>
                            Dashboard
                        </flux:sidebar.item>

                        @if (Route::has('owner.laporan'))
                            <flux:sidebar.item icon="chart-bar" :href="route('owner.laporan')"
                                :current="request()->routeIs('owner.laporan')" wire:navigate>
                                Laporan
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('owner.layanan'))
                            <flux:sidebar.item icon="sparkles" :href="route('owner.layanan')"
                                :current="request()->routeIs('owner.layanan')" wire:navigate>
                                Layanan & Harga
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('owner.pengguna'))
                            <flux:sidebar.item icon="user-group" :href="route('owner.pengguna')"
                                :current="request()->routeIs('owner.pengguna')" wire:navigate>
                                Pengguna
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="$user?->name" />
        </flux:sidebar>

        {{-- Header HP dengan menu geser --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
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
        </flux:header>

        <div class="mx-auto w-full max-w-[1200px] px-4 py-6">
            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
