<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Daftar Pesanan')] class extends Component {
    use WithPagination;

    public string $status = '';

    public string $paymentStatus = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    /**
     * Daftar status pesanan untuk tab filter.
     *
     * @return array<string, string>
     */
    public function statusTabs(): array
    {
        return [
            '' => 'Semua',
            'pending_confirmation' => 'Menunggu Konfirmasi',
            'received' => 'Diterima',
            'washing' => 'Dicuci',
            'ironing' => 'Disetrika',
            'done' => 'Selesai',
            'picked_up' => 'Diambil',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Query dasar dengan semua filter kecuali tab status.
     */
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Order::query()
            ->when($this->search !== '', function ($query): void {
                $keyword = trim($this->search);

                $query->where(function ($query) use ($keyword): void {
                    $query->where('code', 'like', '%'.$keyword.'%')
                        ->orWhereHas('customer', function ($query) use ($keyword): void {
                            $query->where('name', 'like', '%'.$keyword.'%')
                                ->orWhere('phone', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->when($this->paymentStatus !== '', function ($query): void {
                $query->where('payment_status', $this->paymentStatus);
            })
            ->when($this->dateFrom !== '', function ($query): void {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function ($query): void {
                $query->whereDate('created_at', '<=', $this->dateTo);
            });
    }

    /**
     * Jumlah pesanan per status (satu query agregasi).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        $counts = $this->baseQuery()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $counts[''] = array_sum($counts);

        return $counts;
    }

    /**
     * Daftar pesanan terbaru dengan filter aktif.
     */
    #[Computed]
    public function orders(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->baseQuery()
            ->with(['customer', 'items.service'])
            ->when($this->status !== '', function ($query): void {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * Reset pagination ketika filter berubah.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * Ringkasan nama layanan untuk satu pesanan.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\OrderItem>  $items
     */
    private function servicesSummary($items): string
    {
        return $items
            ->groupBy('service.name')
            ->map(fn ($group, $name): string => $name.' ×'.$group->count())
            ->implode(', ');
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Daftar Pesanan" description="Kelola pesanan masuk, pantau status dan pembayaran.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('admin.pesanan.baru')" wire:navigate class="h-11">
                Pesanan Baru
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Tab status --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($this->statusTabs() as $value => $label)
            @php($tabKey = $value === '' ? 'semua' : $value)
            <button type="button" wire:click="$set('status', '{{ $value }}')"
                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition {{ $status === $value ? 'bg-primary-600 text-white' : 'bg-white text-ink-600 hover:bg-primary-50' }} {{ $value === 'pending_confirmation' ? 'ring-2 ring-status-ironing' : '' }}"
                wire:key="tab-{{ $tabKey }}">
                {{ $label }}
                @php($count = $this->statusCounts[$value] ?? 0)
                @if ($count > 0 && $value === 'pending_confirmation')
                    <span class="rounded-full {{ $status === $value ? 'bg-white/20' : 'bg-status-ironing' }} px-2 py-0.5 text-xs font-semibold {{ $status === $value ? 'text-white' : 'text-status-ironing-ink' }}">
                        {{ $count }}
                    </span>
                @elseif ($count > 0)
                    <span class="rounded-full {{ $status === $value ? 'bg-white/20' : 'bg-primary-100' }} px-2 py-0.5 text-xs font-semibold {{ $status === $value ? 'text-white' : 'text-primary-700' }}">
                        {{ $count }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Filter lanjutan --}}
    <div class="mb-4 grid gap-3 rounded-2xl border border-ink-300 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" label="Cari"
            placeholder="Kode pesanan / nama / HP" />

        <flux:select wire:model.live="paymentStatus" label="Status bayar">
            <option value="">Semua status bayar</option>
            <option value="unpaid">Belum Bayar</option>
            <option value="partial">DP</option>
            <option value="paid">Lunas</option>
        </flux:select>

        <flux:input wire:model.live="dateFrom" type="date" label="Dari tanggal" />

        <flux:input wire:model.live="dateTo" type="date" label="Sampai tanggal" />
    </div>

    {{-- Tabel desktop --}}
    <div class="hidden overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm lg:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Kode Pesanan</th>
                    <th class="px-4 py-3 font-semibold">Pelanggan</th>
                    <th class="px-4 py-3 font-semibold">Layanan</th>
                    <th class="px-4 py-3 font-semibold">Berat</th>
                    <th class="px-4 py-3 font-semibold">Total</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Bayar</th>
                    <th class="px-4 py-3 font-semibold">Diperbarui</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->orders() as $order)
                    @php($orderId = $order->id)
                    <tr class="cursor-pointer border-t border-ink-300/60 transition hover:bg-primary-50/60"
                        wire:key="order-desktop-{{ $orderId }}"
                        onclick="window.Livewire.navigate('{{ route('admin.pesanan.show', $order) }}')">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-ink-900">{{ $order->code }}</td>
                        <td class="px-4 py-3 text-ink-900">{{ $order->customer?->name ?? '—' }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-ink-600">{{ $this->servicesSummary($order->items) }}</td>
                        <td class="px-4 py-3 tabular-nums text-ink-600">{{ \App\Support\Money::weight((float) $order->total_weight) }}</td>
                        <td class="px-4 py-3 tabular-nums font-medium text-ink-900">{{ \App\Support\Money::rupiah($order->total_price) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$order->status" size="sm" /></td>
                        <td class="px-4 py-3"><x-payment-badge :status="$order->payment_status" size="sm" /></td>
                        <td class="px-4 py-3 text-xs text-ink-600">{{ $order->updated_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10">
                            <x-empty-state icon="clipboard" title="Belum ada pesanan"
                                description="Pesanan yang dibuat akan muncul di sini. Klik Pesanan Baru untuk mulai." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu HP --}}
    <div class="grid gap-4 lg:hidden">
        @forelse ($this->orders() as $order)
            @php($orderId = $order->id)
            <a href="{{ route('admin.pesanan.show', $order) }}" wire:navigate wire:key="order-mobile-{{ $orderId }}"
                class="block rounded-2xl border border-ink-300 bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <span class="font-mono text-xs font-medium text-ink-900">{{ $order->code }}</span>
                    <x-status-badge :status="$order->status" size="sm" />
                </div>

                <p class="mt-2 font-medium text-ink-900">{{ $order->customer?->name ?? '—' }}</p>
                <p class="mt-1 text-xs text-ink-600">{{ $this->servicesSummary($order->items) }}</p>

                <div class="mt-3 flex items-end justify-between gap-3">
                    <div>
                        <p class="font-display text-lg font-semibold text-primary-600">{{ \App\Support\Money::rupiah($order->total_price) }}</p>
                        <p class="text-xs text-ink-600">{{ \App\Support\Money::weight((float) $order->total_weight) }}</p>
                    </div>
                    <x-payment-badge :status="$order->payment_status" size="sm" />
                </div>
            </a>
        @empty
            <x-empty-state icon="clipboard" title="Belum ada pesanan"
                description="Pesanan yang dibuat akan muncul di sini. Klik Pesanan Baru untuk mulai." />
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->orders()->links() }}
    </div>
</div>
