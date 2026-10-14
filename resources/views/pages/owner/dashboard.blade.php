<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Pemilik')] class extends Component {};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Dashboard Pemilik" description="Halaman khusus pemilik usaha." />

    <x-empty-state icon="squares-2x2" title="Halaman sementara"
        description="Placeholder untuk peran Pemilik. Kartu statistik pendapatan dan grafik akan dibuat pada tahap T6." />
</div>
