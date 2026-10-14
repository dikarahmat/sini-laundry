<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pesanan Saya')] class extends Component {};
?>

<div class="mx-auto w-full max-w-3xl">
    <flux:heading size="xl">Pesanan Saya</flux:heading>
    <flux:subheading>Halaman beranda pelanggan.</flux:subheading>

    <div class="mt-6 rounded-2xl border border-ink-300 bg-white p-6 shadow-sm">
        <p class="text-ink-600">
            Halaman sementara (placeholder) untuk peran <strong>Pelanggan</strong>.
            Daftar pesanan aktif, riwayat, dan tombol pesan online akan dibuat pada tahap T5.
        </p>
    </div>
</div>
