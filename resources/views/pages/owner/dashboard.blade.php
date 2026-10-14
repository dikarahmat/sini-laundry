<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Pemilik')] class extends Component {};
?>

<div class="mx-auto w-full max-w-5xl">
    <flux:heading size="xl">Dashboard Pemilik</flux:heading>
    <flux:subheading>Halaman khusus pemilik usaha.</flux:subheading>

    <div class="mt-6 rounded-2xl border border-ink-300 bg-white p-6 shadow-sm">
        <p class="text-ink-600">
            Halaman sementara (placeholder) untuk peran <strong>Pemilik</strong>.
            Kartu statistik pendapatan dan grafik akan dibuat pada tahap T6.
        </p>
    </div>
</div>
