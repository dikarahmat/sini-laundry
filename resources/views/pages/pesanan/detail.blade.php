<?php

use App\Models\Order;
use App\Services\OrderStatusService;
use App\Support\OrderStatusStyles;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Pesanan')] class extends Component {
    public Order $order;

    public bool $showCancelDialog = false;

    public string $cancelNote = '';

    /**
     * Mount the component.
     */
    public function mount(Order $order): void
    {
        // Pelanggan hanya boleh membuka pesanannya sendiri (OrderPolicy::view).
        Gate::authorize('view', $order);

        $this->order = $order->load(['items.service', 'statusLogs.changer', 'payments.receiver']);
    }

    /**
     * Tahap timeline.
     *
     * @return array<int, string>
     */
    public function timelineStages(): array
    {
        if ($this->order->status?->value === 'cancelled') {
            return ['pending_confirmation', 'received', 'cancelled'];
        }

        if ($this->order->source?->value === 'online') {
            return ['pending_confirmation', 'received', ...OrderStatusStyles::FLOW];
        }

        return OrderStatusStyles::FLOW;
    }

    /**
     * Batalkan pesanan (hanya saat Menunggu Konfirmasi).
     */
    public function cancel(OrderStatusService $service): void
    {
        Gate::authorize('cancel', $this->order);

        try {
            $this->order = $service->cancel(
                $this->order,
                auth()->user(),
                $this->cancelNote !== '' ? $this->cancelNote : null,
            )->load(['items.service', 'statusLogs.changer', 'payments.receiver']);

            $this->showCancelDialog = false;
            $this->cancelNote = '';

            Flux::toast(variant: 'success', text: 'Pesanan dibatalkan.');
        } catch (\RuntimeException $e) {
            $this->showCancelDialog = false;

            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
};
?>

<div class="mx-auto w-full max-w-lg space-y-6">
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('pesanan.index')" wire:navigate />
        <h1 class="font-mono text-lg font-semibold text-ink-900">{{ $order->code }}</h1>
        <x-status-badge :status="$order->status" />
    </div>

    {{-- Timeline --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
        <h2 class="font-display text-base font-semibold text-ink-900">Status Cucian</h2>

        @if ($order->status?->value === 'done')
            <p class="mt-2 rounded-xl bg-status-done p-3 text-sm text-status-done-ink">
                Cucianmu sudah selesai, silakan diambil!
            </p>
        @endif

        <ol class="mt-4 space-y-0">
            @foreach ($this->timelineStages() as $stage)
                @php
                    $reachedIndex = collect($this->timelineStages())->search($order->status?->value);
                    $isCurrent = $order->status?->value === $stage;
                    $isDone = $reachedIndex !== false && $loop->index < $reachedIndex;
                @endphp
                <li class="relative flex gap-4 pb-5 last:pb-0" wire:key="customer-timeline-{{ $stage }}">
                    @if (! $loop->last)
                        <span class="absolute left-[11px] top-6 h-full w-0.5 {{ $isDone ? 'bg-primary-600' : 'bg-ink-300' }}"></span>
                    @endif

                    <span class="relative z-10 mt-1 flex size-6 items-center justify-center rounded-full border-2
                        {{ $isDone ? 'border-primary-600 bg-primary-600' : ($isCurrent ? 'border-primary-600 bg-white motion-safe:animate-pulse' : 'border-ink-300 bg-white') }}">
                        @if ($isDone)
                            <flux:icon icon="check" class="size-3.5 text-white" variant="mini" />
                        @elseif ($isCurrent)
                            <span class="size-2 rounded-full bg-primary-600"></span>
                        @endif
                    </span>

                    <div>
                        <p class="font-medium {{ $isDone || $isCurrent ? 'text-ink-900' : 'text-ink-600' }}">
                            {{ OrderStatusStyles::for($stage)['label'] }}
                        </p>
                        <p class="mt-0.5 text-xs text-ink-600">
                            @if ($log = $order->statusLogs->firstWhere('status.value', $stage))
                                <x-datetime :value="$log->created_at" />
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Item dan total --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
        <h2 class="font-display text-base font-semibold text-ink-900">Rincian</h2>

        <div class="mt-3 space-y-2 text-sm">
            @foreach ($order->items as $item)
                <div class="flex items-center justify-between" wire:key="customer-item-{{ $item->id }}">
                    <span class="text-ink-600">
                        {{ $item->service?->name }}
                        <span class="block text-xs">
                            @if ($item->service?->unit?->value === 'pcs')
                                {{ (int) $item->quantity }} pcs
                            @else
                                {{ \App\Support\Money::weight((float) $item->quantity) }}
                            @endif
                            × {{ \App\Support\Money::rupiah($item->unit_price) }}
                        </span>
                    </span>
                    <span class="tabular-nums text-ink-900">{{ \App\Support\Money::rupiah($item->subtotal) }}</span>
                </div>
            @endforeach

            <div class="flex items-center justify-between border-t border-ink-300/60 pt-3">
                <span class="font-semibold text-ink-900">Total</span>
                <span class="font-display text-lg font-semibold text-primary-600">
                    {{ \App\Support\Money::rupiah($order->total_price) }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-ink-600">Status bayar</span>
                <x-payment-badge :status="$order->payment_status" />
            </div>

            @if ($order->estimated_done_at)
                <div class="flex items-center justify-between">
                    <span class="text-ink-600">Estimasi selesai</span>
                    <span class="text-ink-900"><x-datetime :value="$order->estimated_done_at" /></span>
                </div>
            @endif
        </div>
    </section>

    {{-- Aksi batalkan --}}
    @if ($order->status?->value === 'pending_confirmation')
        <flux:button variant="outline" class="h-11 w-full text-red-600" wire:click="$set('showCancelDialog', true)"
            data-test="customer-cancel-button">
            Batalkan Pesanan
        </flux:button>
    @endif

    <flux:modal name="batal-pesanan" wire:model="showCancelDialog" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Batalkan Pesanan?</flux:heading>

            <flux:text>Pesanan {{ $order->code }} akan dibatalkan.</flux:text>

            <flux:textarea wire:model="cancelNote" rows="2" label="Alasan (opsional)"
                placeholder="Contoh: berubah rencana" />

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="outline" x-on:click="$flux.modal('batal-pesanan').close()">
                    Tutup
                </flux:button>
                <flux:button variant="danger" wire:click="cancel" data-test="customer-confirm-cancel-button">
                    Batalkan Pesanan
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
