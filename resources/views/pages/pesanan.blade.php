<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Beranda')] class extends Component {
    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Customer::ensureForUser(auth()->user());
    }

    /**
     * Pesanan aktif (belum diambil dan belum dibatalkan), terbaru dulu.
     */
    #[Computed]
    public function activeOrder(): ?\App\Models\Order
    {
        return \App\Models\Order::query()
            ->with(['items.service'])
            ->where('customer_id', auth()->user()->customer->id)
            ->whereNotIn('status', [OrderStatus::PickedUp->value, OrderStatus::Cancelled->value])
            ->latest()
            ->first();
    }

    /**
     * Riwayat pesanan terakhir.
     */
    #[Computed]
    public function history()
    {
        return \App\Models\Order::query()
            ->with(['items.service'])
            ->where('customer_id', auth()->user()->customer->id)
            ->latest()
            ->limit(10)
            ->get();
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-ink-900">
            Halo, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}!
        </h1>
        <p class="mt-1 text-sm text-ink-600">Pantau cucian kamu di sini.</p>
    </div>

    {{-- Pesanan aktif --}}
    @if ($this->activeOrder)
        @php($order = $this->activeOrder)
        <a href="{{ route('pesanan.show', $order) }}" wire:navigate
            class="block rounded-2xl border border-ink-300 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-ink-600">Pesanan Aktif</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-ink-900">{{ $order->code }}</p>
                </div>
                <x-status-badge :status="$order->status" />
            </div>

            <p class="mt-3 text-sm text-ink-600">
                {{ $order->items->pluck('service.name')->filter()->implode(', ') }}
            </p>

            <p class="mt-2 text-xs text-ink-600">
                Estimasi selesai:
                @if ($order->estimated_done_at)
                    <x-datetime :value="$order->estimated_done_at" />
                @else
                    menunggu konfirmasi kasir
                @endif
            </p>
        </a>
    @else
        <div class="rounded-2xl border border-dashed border-ink-300 bg-white p-6 text-center">
            <p class="text-sm text-ink-600">Belum ada pesanan aktif.</p>
        </div>
    @endif

    {{-- Tombol besar pesan laundry --}}
    <a href="{{ route('pesanan.baru') }}" wire:navigate
        class="flex h-14 items-center justify-center gap-2 rounded-2xl bg-primary-600 text-base font-semibold text-white shadow-sm transition hover:bg-primary-700">
        <flux:icon icon="plus" class="size-6" variant="mini" />
        Pesan Laundry
    </a>

    {{-- Riwayat --}}
    <section>
        <h2 class="font-display text-base font-semibold text-ink-900">Riwayat Pesanan</h2>

        <div class="mt-3 space-y-3">
            @forelse ($this->history as $order)
                <a href="{{ route('pesanan.show', $order) }}" wire:navigate
                    class="block rounded-2xl border border-ink-300 bg-white p-4 shadow-sm transition hover:shadow-md"
                    wire:key="riwayat-{{ $order->id }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-xs font-medium text-ink-900">{{ $order->code }}</span>
                        <x-status-badge :status="$order->status" size="sm" />
                    </div>
                    <div class="mt-2 flex items-end justify-between gap-3">
                        <span class="text-xs text-ink-600">{{ $order->created_at?->diffForHumans() }}</span>
                        <span class="text-sm font-semibold text-ink-900">{{ \App\Support\Money::rupiah($order->total_price) }}</span>
                    </div>
                </a>
            @empty
                <x-empty-state icon="archive-box" title="Belum ada riwayat"
                    description="Pesanan yang kamu buat akan muncul di sini." />
            @endforelse
        </div>
    </section>
</div>
