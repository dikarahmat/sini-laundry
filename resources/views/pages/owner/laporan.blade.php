<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan Pendapatan')] class extends Component {
    public string $dateFrom = '';

    public string $dateTo = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        abort_unless(auth()->user()->isOwner(), 403);

        // Default: bulan ini.
        $this->dateFrom = \Illuminate\Support\Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = \Illuminate\Support\Carbon::now()->endOfMonth()->toDateString();
    }

    /**
     * Pintasan rentang tanggal.
     */
    public function shortcut(string $range): void
    {
        [$this->dateFrom, $this->dateTo] = match ($range) {
            'today' => [
                \Illuminate\Support\Carbon::today()->toDateString(),
                \Illuminate\Support\Carbon::today()->toDateString(),
            ],
            'last7' => [
                \Illuminate\Support\Carbon::today()->subDays(6)->toDateString(),
                \Illuminate\Support\Carbon::today()->toDateString(),
            ],
            'lastMonth' => [
                \Illuminate\Support\Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                \Illuminate\Support\Carbon::now()->subMonth()->endOfMonth()->toDateString(),
            ],
            default => [
                \Illuminate\Support\Carbon::now()->startOfMonth()->toDateString(),
                \Illuminate\Support\Carbon::now()->endOfMonth()->toDateString(),
            ],
        };
    }

    /**
     * Rentang waktu filter dalam bentuk Carbon.
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function range(): array
    {
        return [
            \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay(),
            \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    /**
     * Total pendapatan dari pembayaran pada rentang tanggal.
     */
    #[Computed]
    public function totalRevenue(): int
    {
        [$from, $to] = $this->range();

        return (int) Payment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');
    }

    /**
     * Jumlah pesanan pada rentang tanggal.
     */
    #[Computed]
    public function orderCount(): int
    {
        [$from, $to] = $this->range();

        return Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Rata-rata nilai pesanan.
     */
    #[Computed]
    public function averageOrderValue(): int
    {
        $count = $this->orderCount;

        if ($count === 0) {
            return 0;
        }

        [$from, $to] = $this->range();

        return (int) round(
            Order::query()->whereBetween('created_at', [$from, $to])->sum('total_price') / $count
        );
    }

    /**
     * Total tagihan belum lunas (pesanan berstatus belum lunas/DP pada rentang tanggal).
     */
    #[Computed]
    public function unpaidTotal(): int
    {
        [$from, $to] = $this->range();

        $orders = Order::query()
            ->select(['id', 'total_price'])
            ->whereBetween('created_at', [$from, $to])
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $paid = (int) Payment::query()->whereIn('order_id', $orders->pluck('id'))->sum('amount');

        return max(0, (int) $orders->sum('total_price') - $paid);
    }

    /**
     * Tabel pendapatan per hari.
     *
     * @return array<int, array{date: string, label: string, orders: int, revenue: int}>
     */
    #[Computed]
    public function dailyRows(): array
    {
        [$from, $to] = $this->range();

        $revenue = Payment::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $orders = Order::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $rows = [];

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();

            $rows[] = [
                'date' => $key,
                'label' => $date->translatedFormat('j M Y'),
                'orders' => $orders[$key] ?? 0,
                'revenue' => $revenue[$key] ?? 0,
            ];
        }

        return $rows;
    }

    /**
     * Tabel layanan terlaris pada rentang tanggal.
     */
    #[Computed]
    public function serviceRows()
    {
        [$from, $to] = $this->range();

        return \App\Models\OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('services', 'services.id', '=', 'order_items.service_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNot('orders.status', \App\Enums\OrderStatus::Cancelled->value)
            ->groupBy('services.id', 'services.name', 'services.unit')
            ->selectRaw('services.name, services.unit, COUNT(*) as order_count, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as revenue')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Rincian per metode pembayaran.
     */
    #[Computed]
    public function paymentMethodRows()
    {
        [$from, $to] = $this->range();

        return Payment::query()
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as jumlah')
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy('method')
            ->get();
    }
};
?>

<div class="mx-auto w-full max-w-5xl space-y-6">
    <style>
        @media print {
            flux-sidebar,
            flux-header,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="flex flex-wrap items-start justify-between gap-4 no-print">
        <x-page-header title="Laporan Pendapatan"
            description="Pendapatan, jumlah pesanan, dan layanan terlaris berdasarkan rentang tanggal." />

        <flux:button variant="outline" icon="printer" class="h-11" onclick="window.print()">
            Cetak Laporan
        </flux:button>
    </div>

    {{-- Filter --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-4 shadow-sm no-print">
        <div class="flex flex-wrap items-end gap-3">
            <flux:input wire:model.live="dateFrom" type="date" label="Dari tanggal" class="w-44" />
            <flux:input wire:model.live="dateTo" type="date" label="Sampai tanggal" class="w-44" />

            <div class="flex flex-wrap gap-2 pb-1">
                <flux:button size="sm" variant="outline" wire:click="shortcut('today')">Hari ini</flux:button>
                <flux:button size="sm" variant="outline" wire:click="shortcut('last7')">7 hari</flux:button>
                <flux:button size="sm" variant="outline" wire:click="shortcut('thisMonth')">Bulan ini</flux:button>
                <flux:button size="sm" variant="outline" wire:click="shortcut('lastMonth')">Bulan lalu</flux:button>
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card title="Total Pendapatan" :value="\App\Support\Money::rupiah($this->totalRevenue)" icon="banknotes"
            description="Dari pembayaran pada periode" />
        <x-stat-card title="Jumlah Pesanan" :value="$this->orderCount" icon="clipboard"
            description="Pesanan dibuat pada periode" />
        <x-stat-card title="Rata-rata Nilai Pesanan" :value="\App\Support\Money::rupiah($this->averageOrderValue)"
            icon="calculator" description="Total pesanan / jumlah pesanan" />
        <x-stat-card title="Belum Lunas" :value="\App\Support\Money::rupiah($this->unpaidTotal)" icon="document-currency-dollar"
            description="Sisa tagihan periode" />
    </div>

    {{-- Pendapatan per hari --}}
    <section class="overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm">
        <div class="border-b border-ink-300/60 p-5">
            <h2 class="font-display text-base font-semibold text-ink-900">Pendapatan per Hari</h2>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-5 py-3 font-semibold">Tanggal</th>
                    <th class="px-5 py-3 font-semibold">Jumlah Pesanan</th>
                    <th class="px-5 py-3 text-end font-semibold">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->dailyRows as $row)
                    <tr class="border-t border-ink-300/60" wire:key="daily-{{ $row['date'] }}">
                        <td class="px-5 py-3 text-ink-900">{{ $row['label'] }}</td>
                        <td class="px-5 py-3 tabular-nums text-ink-600">{{ $row['orders'] }}</td>
                        <td class="px-5 py-3 text-end tabular-nums font-medium text-ink-900">
                            {{ \App\Support\Money::rupiah($row['revenue']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-6 text-sm text-ink-600">Tidak ada data pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t border-ink-300/60 bg-primary-50/50">
                    <td class="px-5 py-3 font-semibold text-ink-900">Total</td>
                    <td class="px-5 py-3 tabular-nums font-semibold text-ink-900">{{ $this->orderCount }}</td>
                    <td class="px-5 py-3 text-end font-display font-semibold text-primary-600">
                        {{ \App\Support\Money::rupiah($this->totalRevenue) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Layanan terlaris --}}
        <section class="overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm">
            <div class="border-b border-ink-300/60 p-5">
                <h2 class="font-display text-base font-semibold text-ink-900">Layanan Terlaris</h2>
            </div>

            <table class="w-full text-left text-sm">
                <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Layanan</th>
                        <th class="px-5 py-3 font-semibold">Pesanan</th>
                        <th class="px-5 py-3 font-semibold">Berat/Qty</th>
                        <th class="px-5 py-3 text-end font-semibold">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->serviceRows as $row)
                        <tr class="border-t border-ink-300/60" wire:key="service-row-{{ $row->name }}">
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $row->name }}</td>
                            <td class="px-5 py-3 tabular-nums text-ink-600">{{ $row->order_count }}</td>
                            <td class="px-5 py-3 tabular-nums text-ink-600">
                                @if ($row->unit === 'pcs')
                                    {{ (int) $row->total_quantity }} pcs
                                @else
                                    {{ \App\Support\Money::weight((float) $row->total_quantity) }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-end tabular-nums font-medium text-ink-900">
                                {{ \App\Support\Money::rupiah((int) $row->revenue) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-sm text-ink-600">Belum ada data layanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Metode pembayaran --}}
        <section class="overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm">
            <div class="border-b border-ink-300/60 p-5">
                <h2 class="font-display text-base font-semibold text-ink-900">Metode Pembayaran</h2>
            </div>

            <table class="w-full text-left text-sm">
                <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Metode</th>
                        <th class="px-5 py-3 font-semibold">Jumlah Transaksi</th>
                        <th class="px-5 py-3 text-end font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paymentMethodRows as $row)
                        <tr class="border-t border-ink-300/60" wire:key="method-row-{{ $row->method }}">
                            <td class="px-5 py-3 font-medium text-ink-900">
                                @php($method = $row->method instanceof \App\Enums\PaymentMethod ? $row->method : \App\Enums\PaymentMethod::tryFrom((string) $row->method))
                                {{ $method?->label() ?? $row->method }}
                            </td>
                            <td class="px-5 py-3 tabular-nums text-ink-600">{{ $row->jumlah }}</td>
                            <td class="px-5 py-3 text-end tabular-nums font-medium text-ink-900">
                                {{ \App\Support\Money::rupiah((int) $row->total) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-sm text-ink-600">Belum ada pembayaran pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
