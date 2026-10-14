<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Daftar Pesanan')] class extends Component {};
?>

<div class="mx-auto w-full max-w-5xl">
    <flux:heading size="xl">Daftar Pesanan</flux:heading>
    <flux:subheading>Halaman admin dan pemilik.</flux:subheading>

    <div class="mt-6 rounded-2xl border border-ink-300 bg-white p-6 shadow-sm">
        <p class="text-ink-600">
            Halaman sementara (placeholder) untuk peran <strong>Admin</strong> dan <strong>Pemilik</strong>.
            Daftar pesanan lengkap dengan filter status, pencarian, dan paginasi akan dibuat pada tahap T3.5.
        </p>
    </div>
</div>
