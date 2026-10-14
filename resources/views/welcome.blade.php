<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Sini Laundry') }} — Cucianmu sudah sampai mana? Cek langsung di Sini.</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-surface font-sans text-ink-900 antialiased">
        @php
            // Layanan aktif untuk ditampilkan di landing page (P5).
            $services = \App\Models\Service::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get();
        @endphp

        {{-- Navbar --}}
        <header class="sticky top-0 z-30 border-b border-ink-300 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-5xl items-center gap-4 px-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <span class="flex size-9 items-center justify-center rounded-xl bg-primary-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                            <path d="M12 2.7c3.3 3.5 5.8 6.7 5.8 9.8a5.8 5.8 0 1 1-11.6 0c0-3.1 2.5-6.3 5.8-9.8Z" />
                            <path d="M9.3 13.6a2.9 2.9 0 0 0 2.2 3.1" stroke-width="1.6" />
                        </svg>
                    </span>
                    <span class="font-display text-lg font-semibold text-ink-900">{{ config('app.name', 'Sini Laundry') }}</span>
                </a>

                <div class="flex-1"></div>

                <nav class="flex items-center gap-2 text-sm font-medium">
                    <a href="{{ url('/cek-status') }}"
                        class="hidden rounded-xl px-3 py-2 text-ink-600 transition hover:bg-primary-50 hover:text-primary-700 sm:block">
                        Cek Status
                    </a>

                    @auth
                        <a href="{{ route('pesanan.index') }}" wire:navigate
                            class="inline-flex h-11 items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700">
                            Pesan Laundry
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                            class="inline-flex h-11 items-center rounded-xl px-3 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                            class="inline-flex h-11 items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700">
                            Daftar
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="mx-auto max-w-5xl px-4 pb-12 pt-10 md:pt-16">
                <div class="grid items-center gap-10 md:grid-cols-2">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700">
                            <flux:icon icon="sparkles" class="size-4" variant="mini" />
                            Laundry kiloan & satuan
                        </span>

                        <h1 class="mt-4 font-display text-2xl font-semibold leading-tight text-ink-900 md:text-[28px]">
                            Cucianmu sudah sampai mana? Cek langsung di Sini.
                        </h1>

                        <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-600 md:text-base">
                            Tidak perlu chat atau datang ke toko hanya untuk bertanya. Pesan laundry,
                            pantau status cucian secara transparan, dan lihat tagihan dengan harga
                            yang dihitung otomatis.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('pesanan.index') }}" wire:navigate
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 text-sm font-semibold text-white transition hover:bg-primary-700">
                                    <flux:icon icon="plus" class="size-5" variant="mini" />
                                    Pesan Laundry
                                </a>
                            @else
                                <a href="{{ route('register') }}" wire:navigate
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 text-sm font-semibold text-white transition hover:bg-primary-700">
                                    <flux:icon icon="rocket-launch" class="size-5" variant="mini" />
                                    Pesan Laundry
                                </a>
                            @endauth

                            <a href="{{ url('/cek-status') }}"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-primary-600 bg-white px-6 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
                                <flux:icon icon="magnifying-glass" class="size-5" variant="mini" />
                                Cek Status Cucian
                            </a>
                        </div>
                    </div>

                    {{-- Ilustrasi gelembung sabun (SVG/CSS, tanpa gambar eksternal) --}}
                    <div class="relative mx-auto w-full max-w-sm" aria-hidden="true">
                        <div class="relative aspect-square w-full">
                            <div class="absolute inset-0 rounded-full bg-primary-100 opacity-70 blur-2xl"></div>

                            <svg class="absolute inset-0 size-full" viewBox="0 0 320 320" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="96" cy="96" r="54" fill="#DBEEFE" />
                                <circle cx="212" cy="120" r="72" fill="#EFF8FF" />
                                <circle cx="150" cy="216" r="88" fill="#F8FAFC" />

                                <circle cx="96" cy="96" r="42" stroke="#2B9BF4" stroke-width="2" opacity=".5" />
                                <circle cx="212" cy="120" r="58" stroke="#2DD4BF" stroke-width="2" opacity=".55" />
                                <circle cx="150" cy="216" r="72" stroke="#1479D6" stroke-width="2" opacity=".35" />

                                <circle cx="88" cy="86" r="9" fill="#FFFFFF" opacity=".9" />
                                <circle cx="204" cy="108" r="11" fill="#FFFFFF" opacity=".85" />

                                <path d="M60 246c26-10 40-26 44-50" stroke="#2DD4BF" stroke-width="2"
                                    stroke-linecap="round" opacity=".7" />
                                <path d="M232 196c10 18 8 36-4 52" stroke="#2B9BF4" stroke-width="2"
                                    stroke-linecap="round" opacity=".6" />

                                {{-- Keranjang cucian sederhana --}}
                                <rect x="118" y="150" width="64" height="44" rx="8" fill="#1479D6" />
                                <path d="M126 150c4-14 12-20 24-20s20 6 24 20" stroke="#1479D6" stroke-width="4"
                                    stroke-linecap="round" />
                                <path d="M126 164h48M126 176h48" stroke="#DBEEFE" stroke-width="3"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Cara kerja --}}
            <section class="border-y border-ink-300 bg-white py-12">
                <div class="mx-auto max-w-5xl px-4">
                    <h2 class="text-center font-display text-xl font-semibold text-ink-900 md:text-2xl">
                        Cara kerja
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-center text-sm text-ink-600">
                        Empat langkah sederhana dari pesan sampai cucian diambil.
                    </p>

                    <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['icon' => 'clipboard-document-list', 'judul' => 'Pesan', 'deskripsi' => 'Daftar atau datang ke toko, pilih layanan yang kamu butuhkan.'],
                            ['icon' => 'arrow-path', 'judul' => 'Kami Cuci', 'deskripsi' => 'Admin menimbang cucian dan memasukkan status setiap tahap pencucian.'],
                            ['icon' => 'magnifying-glass', 'judul' => 'Pantau Status', 'deskripsi' => 'Cek status cucian kapan saja lewat kode pesanan tanpa harus bertanya.'],
                            ['icon' => 'shopping-bag', 'judul' => 'Ambil', 'deskripsi' => 'Datang saat status "Selesai", bayar, dan cucian siap dibawa pulang.'],
                        ] as $langkah)
                            <li class="rounded-2xl border border-ink-300 bg-surface p-5 shadow-sm">
                                <div class="flex size-10 items-center justify-center rounded-xl bg-primary-600 text-white">
                                    <flux:icon :icon="$langkah['icon']" class="size-5" variant="mini" />
                                </div>
                                <h3 class="mt-3 font-display text-base font-semibold text-ink-900">{{ $langkah['judul'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $langkah['deskripsi'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Layanan dan harga --}}
            <section class="py-12">
                <div class="mx-auto max-w-5xl px-4">
                    <h2 class="text-center font-display text-xl font-semibold text-ink-900 md:text-2xl">
                        Layanan dan harga
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-center text-sm text-ink-600">
                        Harga dihitung otomatis dari berat cucian setelah ditimbang.
                    </p>

                    {{-- Data dari tabel services (P5), hanya yang aktif. --}}
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($services as $layanan)
                            <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm transition hover:shadow-md">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-display text-base font-semibold text-ink-900">{{ $layanan->name }}</h3>
                                    <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700">
                                        {{ $layanan->unit->label() }}
                                    </span>
                                </div>
                                <p class="mt-3 font-display text-xl font-semibold text-primary-600">
                                    {{ \App\Support\Money::rupiah($layanan->price) }}
                                </p>
                                <p class="mt-1 text-xs text-ink-600">
                                    Estimasi selesai {{ $layanan->duration_hours }} jam
                                    @if ($layanan->is_express)
                                        <span class="ms-1 rounded-full bg-status-ironing px-2 py-0.5 text-xs font-medium text-status-ironing-ink">Express</span>
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-600">Daftar layanan belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Keunggulan --}}
            <section class="border-t border-ink-300 bg-white py-12">
                <div class="mx-auto max-w-5xl px-4">
                    <h2 class="text-center font-display text-xl font-semibold text-ink-900 md:text-2xl">
                        Kenapa Sini Laundry?
                    </h2>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['icon' => 'document-check', 'judul' => 'Tanpa nota hilang', 'deskripsi' => 'Semua pesanan tercatat digital dengan kode pesanan unik, bukan nota kertas.'],
                            ['icon' => 'eye', 'judul' => 'Status transparan', 'deskripsi' => 'Pelanggan bisa memantau status cucian sendiri: Dicuci, Disetrika, sampai Selesai.'],
                            ['icon' => 'calculator', 'judul' => 'Harga otomatis', 'deskripsi' => 'Total dihitung dari berat dan layanan, jadi risiko salah hitung lebih kecil.'],
                            ['icon' => 'chart-bar', 'judul' => 'Laporan rapi', 'deskripsi' => 'Pemilik melihat pendapatan harian dan layanan terlaris tanpa rekap manual.'],
                        ] as $keunggulan)
                            <div class="flex gap-4 rounded-2xl border border-ink-300 bg-surface p-5 shadow-sm">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                                    <flux:icon :icon="$keunggulan['icon']" class="size-6" variant="outline" />
                                </div>
                                <div>
                                    <h3 class="font-display text-base font-semibold text-ink-900">{{ $keunggulan['judul'] }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $keunggulan['deskripsi'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-ink-300 bg-white py-8">
            <div class="mx-auto max-w-5xl px-4 text-sm text-ink-600">
                <p class="font-display text-base font-semibold text-ink-900">{{ config('app.name', 'Sini Laundry') }}</p>
                {{-- Placeholder: ganti dengan alamat dan jam buka usaha yang sebenarnya. --}}
                <p class="mt-2">Alamat: Jl. Bersih Raya No. 12, Kota Kamu</p>
                <p>Jam buka: Senin–Sabtu, 08.00–20.00 WIB</p>
                <p class="mt-4 text-xs">© {{ date('Y') }} Sini Laundry. Dibuat untuk tugas mata kuliah Sistem Informasi Manajemen.</p>
            </div>
        </footer>
    </body>
</html>
