<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Pesanan')] class extends Component {
    /**
     * Halaman sementara: detail lengkap dengan timeline, ubah status, dan
     * pembayaran akan dibuat pada tahap T3.6 (P10).
     */
    public ?\App\Models\Order $order = null;

    public function mount(\App\Models\Order $order): void
    {
        $this->order = $order;
    }
};
?>

<div class="mx-auto w-full max-w-3xl">
    <x-page-header title="Detail Pesanan"
        description="Halaman sementara (placeholder). Detail pesanan, timeline status, ubah status, dan pembayaran akan dibuat pada tahap T3.6." />
</div>
