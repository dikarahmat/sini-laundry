<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Daftar Pesanan')] class extends Component {};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Daftar Pesanan" description="Halaman admin dan pemilik." />

    <x-empty-state icon="queue-list" title="Halaman sementara"
        description="Placeholder untuk peran Admin dan Pemilik. Daftar pesanan lengkap dengan filter status, pencarian, dan paginasi akan dibuat pada tahap T3.5." />
</div>
