<?php

use App\Models\Order;
use App\Support\OrderStatusStyles;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Cek Status Cucian')] class extends Component {
    public string $code = '';

    public string $lastFour = '';

    public ?Order $order = null;

    public bool $searched = false;

    /**
     * Isi otomatis kode pesanan dari parameter URL (?kode=...).
     */
    public function mount(): void
    {
        $codeFromUrl = request()->query('kode');

        if (is_string($codeFromUrl) && $codeFromUrl !== '') {
            $this->code = $codeFromUrl;
        }
    }

    /**
     * Pesan ramah sesuai status cucian.
     */
    public function friendlyMessage(?Order $order): ?string
    {
        if ($order === null) {
            return null;
        }

        return match ($order->status?->value) {
            'pending_confirmation' => 'Pesanan kamu sudah kami terima dan menunggu dikonfirmasi kasir.',
            'received' => 'Cucian kamu sudah diterima kasir dan segera dicuci.',
            'washing' => 'Cucian kamu sedang dicuci. Sabar ya!',
            'ironing' => 'Cucian kamu sedang disetrika biar rapi.',
            'done' => 'Cucianmu sudah selesai, silakan diambil!',
            'picked_up' => 'Cucian sudah diambil. Terima kasih!',
            'cancelled' => 'Pesanan ini telah dibatalkan. Hubungi kasir untuk informasi lebih lanjut.',
            default => null,
        };
    }

    /**
     * Tahap timeline untuk ditampilkan (tanpa nama petugas).
     *
     * @return array<int, string>
     */
    public function timelineStages(?Order $order): array
    {
        if (! $order instanceof Order) {
            return [];
        }

        if ($order->status?->value === 'cancelled') {
            return ['received', 'cancelled'];
        }

        if ($order->source?->value === 'online') {
            return ['pending_confirmation', 'received', ...OrderStatusStyles::FLOW];
        }

        return OrderStatusStyles::FLOW;
    }

    /**
     * Nama pelanggan versi aman: nama depan saja.
     */
    public function safeCustomerName(?Order $order): string
    {
        $name = $order?->customer?->name ?? '';

        return \Illuminate\Support\Str::before(trim($name), ' ') ?: 'Pelanggan';
    }

    /**
     * Periksa pesanan berdasarkan kode + 4 digit terakhir HP.
     */
    public function check(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:20'],
            'lastFour' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ], [], [
            'code' => 'kode pesanan',
            'lastFour' => '4 digit terakhir nomor HP',
        ]);

        // Maksimal 5 percobaan per menit per alamat IP.
        if (RateLimiter::tooManyAttempts(request()->ip(), 5)) {
            $seconds = RateLimiter::availableIn(request()->ip());

            $this->addError('code', "Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik.");

            return;
        }

        RateLimiter::hit(request()->ip(), 60);

        $order = Order::with(['customer', 'items.service'])
            ->where('code', trim($this->code))
            ->first();

        $this->searched = true;

        // Pesan generik yang sama agar kode tidak bisa ditebak.
        if (! $order instanceof Order
            || $order->customer === null
            || mb_substr(preg_replace('/\D/', '', $order->customer->phone) ?? '', -4) !== $this->lastFour) {
            $this->order = null;
            $this->addError('code', 'Data tidak ditemukan, periksa kembali.');

            return;
        }

        $this->order = $order;
    }

    /**
     * Cek pesanan lain (reset form).
     */
    public function resetForm(): void
    {
        $this->code = '';
        $this->lastFour = '';
        $this->order = null;
        $this->searched = false;
        $this->resetValidation();
    }
};
?>

<div class="space-y-6">
    @if (! $order)
        <x-page-header title="Cek Status Cucian"
            description="Masukkan kode pesanan dan 4 digit terakhir nomor HP kamu." />

        <form wire:submit="check" class="space-y-4">
            <flux:input wire:model="code" label="Kode Pesanan" placeholder="SL-20261014-001" required
                class="font-mono" />

            <flux:input wire:model="lastFour" label="4 Digit Terakhir Nomor HP" placeholder="7890" required
                inputmode="numeric" maxlength="4" />

            <flux:button type="submit" variant="primary" class="h-12 w-full" data-test="check-status-button">
                Cek Status
            </flux:button>
        </form>
    @else
        {{-- Hasil --}}
        <div class="rounded-2xl border border-ink-300 bg-white p-6 text-center shadow-sm">
            <p class="text-sm text-ink-600">Halo, {{ $this->safeCustomerName($order) }}!</p>

            <div class="mt-3 flex justify-center">
                <x-status-badge :status="$order->status" />
            </div>

            <p class="mt-3 text-sm text-ink-600">{{ $this->friendlyMessage($order) }}</p>

            <p class="mt-1 font-mono text-xs text-ink-600">{{ $order->code }}</p>
        </div>

        {{-- Timeline --}}
        <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
            <ol class="space-y-0">
                @foreach ($this->timelineStages($order) as $stage)
                    @php
                        $reachedIndex = collect($this->timelineStages($order))->search($order->status?->value);
                        $isCurrent = $order->status?->value === $stage;
                        $isDone = $reachedIndex !== false && $loop->index < $reachedIndex;
                    @endphp
                    <li class="relative flex gap-4 pb-5 last:pb-0" wire:key="public-timeline-{{ $stage }}">
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

                        <p class="font-medium {{ $isDone || $isCurrent ? 'text-ink-900' : 'text-ink-600' }}">
                            {{ OrderStatusStyles::for($stage)['label'] }}
                        </p>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Ringkasan --}}
        <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-ink-600">Layanan</dt>
                    <dd class="text-end text-ink-900">
                        {{ $order->items->pluck('service.name')->filter()->implode(', ') }}
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-600">Total</dt>
                    <dd class="font-semibold text-ink-900">{{ \App\Support\Money::rupiah($order->total_price) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-600">Status bayar</dt>
                    <dd><x-payment-badge :status="$order->payment_status" /></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-600">Estimasi selesai</dt>
                    <dd class="text-ink-900">
                        @if ($order->estimated_done_at)
                            <x-datetime :value="$order->estimated_done_at" />
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <flux:button variant="outline" class="h-11 w-full" wire:click="resetForm">
            Cek Pesanan Lain
        </flux:button>
    @endif
</div>
