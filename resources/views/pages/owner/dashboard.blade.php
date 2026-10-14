<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Pemilik')] class extends Component {
    /**
     * Mount the component.
     */
    public function mount(): void
    {
        abort_unless(auth()->user()->isOwner(), 403);
    }

    /**
     * Pendapatan hari ini (jumlah pembayaran hari ini).
     */
    #[Computed]
    public function todayRevenue(): int
    {
        return (int) Payment::query()
            ->whereDate('paid_at', \Illuminate\Support\Carbon::today())
            ->sum('amount');
    }

    /**
     * Pesanan aktif (belum selesai, belum diambil, belum dibatalkan).
     */
    #[Computed]
    public function activeOrders(): int
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::PendingConfirmation->value,
                OrderStatus::Received->value,
                OrderStatus::Washing->value,
                OrderStatus::Ironing->value,
            ])
            ->count();
    }

    /**
     * Pesanan selesai yang belum diambil.
     */
    #[Computed]
    public function doneNotPickedUp(): int
    {
        return Order::query()->where('status', OrderStatus::Done->value)->count();
    }

    /**
     * Total tagihan belum lunas (pesanan belum bayar + DP).
     */
    #[Computed]
    public function unpaidTotal(): int
    {
        $orders = Order::query()
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->get(['id', 'total_price']);

        if ($orders->isEmpty()) {
            return 0;
        }

        $paid = (int) Payment::query()->whereIn('order_id', $orders->pluck('id'))->sum('amount');

        return max(0, (int) $orders->sum('total_price') - $paid);
    }

    /**
     * Grafik pendapatan 7 hari terakhir (tanggal => nominal).
     *
     * @return array<int, array{date: string, label: string, total: int}>
     */
    #[Computed]
    public function dailyRevenue(): array
    {
        $start = \Illuminate\Support\Carbon::today()->subDays(6);

        $totals = Payment::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->whereDate('paid_at', '>=', $start->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $days[] = [
                'date' => $key,
                'label' => $date->translatedFormat('D'),
                'total' => $totals[$key] ?? 0,
            ];
        }

        return $days;
    }

    /**
     * Nilai maksimum grafik (untuk skala tinggi batang).
     */
    #[Computed]
    public function maxDailyRevenue(): int
    {
        return max(1, max(array_map(fn (array $day): int => $day['total'], $this->dailyRevenue)));
    }

    /**
     * Daftar "Perlu Perhatian".
     *
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, Order>>
     */
    #[Computed]
    public function attentionLists(): array
    {
        $threeDaysAgo = \Illuminate\Support\Carbon::now()->subDays(3);

        return [
            'Selesai belum diambil > 3 hari' => Order::query()
                ->with('customer')
                ->where('status', OrderStatus::Done->value)
                ->where('updated_at', '<=', $threeDaysAgo)
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'Menunggu konfirmasi pesanan online' => Order::query()
                ->with('customer')
                ->where('status', OrderStatus::PendingConfirmation->value)
                ->latest()
                ->limit(5)
                ->get(),
            'Tagihan belum lunas' => Order::query()
                ->with('customer')
                ->where('payment_status', '!=', PaymentStatus::Paid->value)
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::PickedUp->value])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Lima pesanan terbaru.
     */
    #[Computed]
    public function latestOrders()
    {
        return Order::query()
            ->with(['customer', 'items.service'])
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

<div class="mx-auto w-full max-w-6xl space-y-8">
    <x-page-header title="Dashboard Pemilik" description="Ringkasan usaha hari ini." />

    {{-- Kartu statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card title="Pendapatan Hari Ini" :value="\App\Support\Money::rupiah($this->todayRevenue)" icon="banknotes"
            description="Total pembayaran diterima hari ini" />
        <x-stat-card title="Pesanan Aktif" :value="$this->activeOrders" icon="arrow-path"
            description="Sedang diproses" />
        <x-stat-card title="Selesai Belum Diambil" :value="$this->doneNotPickedUp" icon="archive-box"
            description="Menunggu diambil pelanggan" />
        <x-stat-card title="Tagihan Belum Lunas" :value="\App\Support\Money::rupiah($this->unpaidTotal)" icon="document-currency-dollar"
            description="Belum bayar + DP" />
    </div>

    {{-- Grafik pendapatan 7 hari --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
        <h2 class="font-display text-base font-semibold text-ink-900">Pendapatan 7 Hari Terakhir</h2>

        @php($max = $this->maxDailyRevenue)
        <div class="mt-6 flex items-end justify-between gap-2">
            @foreach ($this->dailyRevenue as $day)
                <div class="flex flex-1 flex-col items-center gap-2" wire:key="chart-{{ $day['date'] }}">
                    <span class="text-[10px] leading-tight text-ink-600">{{ \App\Support\Money::rupiah($day['total']) }}</span>
                    <div class="flex h-32 w-full items-end justify-center">
                        <div class="w-full max-w-10 rounded-t-xl {{ $day['total'] > 0 ? 'bg-primary-600' : 'bg-ink-300/40' }} transition-all"
                            style="height: {{ max(2, (int) round($day['total'] / $max * 100)) }}%"
                            role="img" aria-label="{{ $day['label'] }}: {{ \App\Support\Money::rupiah($day['total']) }}">
                        </div>
                    </div>
                    <span class="text-xs font-medium text-ink-600">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Perlu perhatian --}}
        <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
            <h2 class="font-display text-base font-semibold text-ink-900">Perlu Perhatian</h2>

            <div class="mt-4 space-y-5">
                @foreach ($this->attentionLists as $judul => $orders)
                    <div wire:key="attention-{{ \Illuminate\Support\Str::slug($judul) }}">
                        <h3 class="text-sm font-medium text-ink-600">{{ $judul }}</h3>

                        @if ($orders->isEmpty())
                            <p class="mt-2 text-sm text-ink-600">Tidak ada.</p>
                        @else
                            <ul class="mt-2 divide-y divide-ink-300/60">
                                @foreach ($orders as $order)
                                    @php($attentionOrderId = $order->id)
                                    <li wire:key="attention-order-{{ $attentionOrderId }}">
                                        <a href="{{ route('admin.pesanan.show', $order) }}" wire:navigate
                                            class="flex items-center justify-between gap-3 py-2 transition hover:text-primary-700">
                                            <span class="font-mono text-xs text-ink-900">{{ $order->code }}</span>
                                            <span class="truncate text-sm text-ink-600">{{ $order->customer?->name }}</span>
                                            <span class="text-sm tabular-nums text-ink-900">
                                                {{ \App\Support\Money::rupiah($order->total_price) }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Pesanan terbaru --}}
        <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
            <h2 class="font-display text-base font-semibold text-ink-900">Pesanan Terbaru</h2>

            <ul class="mt-4 divide-y divide-ink-300/60">
                @forelse ($this->latestOrders as $order)
                    @php($latestOrderId = $order->id)
                    <li wire:key="latest-order-{{ $latestOrderId }}">
                        <a href="{{ route('admin.pesanan.show', $order) }}" wire:navigate
                            class="flex items-center justify-between gap-3 py-3 transition hover:text-primary-700">
                            <div>
                                <p class="font-mono text-xs font-medium text-ink-900">{{ $order->code }}</p>
                                <p class="text-xs text-ink-600">{{ $order->customer?->name }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm tabular-nums text-ink-900">{{ \App\Support\Money::rupiah($order->total_price) }}</span>
                                <x-status-badge :status="$order->status" size="sm" />
                            </div>
                        </a>
                    </li>
                @empty
                    <li>
                        <x-empty-state icon="clipboard" title="Belum ada pesanan" />
                    </li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
