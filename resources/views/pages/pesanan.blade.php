<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pesanan Saya')] class extends Component {};
?>

<div class="mx-auto w-full max-w-3xl">
    <x-page-header title="Pesanan Saya" description="Beranda pelanggan." />

    <x-empty-state icon="archive-box" title="Belum ada pesanan"
        description="Halaman sementara (placeholder) untuk peran Pelanggan. Daftar pesanan aktif, riwayat, dan tombol pesan online akan dibuat pada tahap T5." />
</div>
