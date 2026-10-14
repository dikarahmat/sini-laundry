<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderPricingService;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use App\Support\Money;
use App\Support\OrderStatusStyles;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Pesanan')] class extends Component {
    public Order $order;

    public string $rollbackNote = '';

    public bool $showRollbackForm = false;

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    public bool $showCancelDialog = false;

    public string $cancelNote = '';

    public bool $showConfirmPanel = false;

    public bool $showRejectDialog = false;

    public string $rejectReason = '';

    /**
     * Item yang sedang ditimbang/dikonfirmasi (kunci = order_items.id).
     *
     * @var array<int, array{service_id: string, quantity: string}>
     */
    public array $confirmingItems = [];

    /**
     * Mount the component.
     */
    public function mount(Order $order): void
    {
        $this->order = $order->load(['customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator']);

        // Isi awal form konfirmasi dengan estimasi pelanggan.
        foreach ($this->order->items as $item) {
            $this->confirmingItems[$item->id] = [
                'service_id' => (string) $item->service_id,
                'quantity' => (string) $item->quantity,
            ];
        }
    }

    /**
     * Apakah pesanan sudah lunas.
     */
    #[Computed]
    public function isPaid(): bool
    {
        return $this->order->payment_status?->value === 'paid';
    }

    /**
     * Sisa tagihan.
     */
    #[Computed]
    public function remainingAmount(): int
    {
        return $this->order->remainingAmount();
    }

    /**
     * Tahap status normal untuk timeline.
     *
     * @return array<int, string>
     */
    public function timelineStages(): array
    {
        if ($this->order->status?->value === OrderStatus::Cancelled->value) {
            return [OrderStatus::Received->value, OrderStatus::Cancelled->value];
        }

        if ($this->order->source?->value === 'online') {
            return [
                OrderStatus::PendingConfirmation->value,
                OrderStatus::Received->value,
                ...OrderStatusStyles::FLOW,
            ];
        }

        return OrderStatusStyles::FLOW;
    }

    /**
     * Status berikutnya dalam alur.
     */
    public function nextStatus(): ?OrderStatus
    {
        return $this->order->status?->next();
    }

    /**
     * Perhitungan ulang harga berdasarkan item yang dikonfirmasi.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, total_weight: float}
     */
    #[Computed]
    public function confirmPricing(): array
    {
        $items = array_filter($this->confirmingItems, fn (array $item): bool => $item['service_id'] !== '' && (float) $item['quantity'] > 0);

        if ($items === []) {
            return ['items' => [], 'total' => 0, 'total_weight' => 0.0];
        }

        $items = array_map(fn (array $item): array => [
            'service_id' => (int) $item['service_id'],
            'quantity' => (float) $item['quantity'],
        ], $items);

        return app(OrderPricingService::class)->calculate($items);
    }

    /**
     * Konfirmasi pesanan online: perbarui item dan total, lalu terima cucian.
     */
    public function confirmOrder(OrderPricingService $pricing, OrderStatusService $service): void
    {
        Gate::authorize('changeStatus', $this->order);

        $validated = $this->validate([
            'confirmingItems' => ['required', 'array', 'min:1'],
            'confirmingItems.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'confirmingItems.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], [
            'confirmingItems.required' => 'Isi berat aktual untuk setiap item.',
            'confirmingItems.*.quantity.gt' => 'Berat harus lebih dari 0 kg.',
        ]);

        $result = $pricing->calculate($validated['confirmingItems']);

        $order = DB::transaction(function () use ($pricing, $result, $service): Order {
            // Perbarui item dan harga sesuai timbangan aktual (dengan pembulatan yang sama).
            foreach ($this->confirmingItems as $itemId => $confirmed) {
                $item = $this->order->items->firstWhere('id', (int) $itemId);

                if ($item === null) {
                    continue;
                }

                $single = $pricing->calculate([[
                    'service_id' => (int) $confirmed['service_id'],
                    'quantity' => (float) $confirmed['quantity'],
                ]]);

                $priced = $single['items'][0];

                $item->update([
                    'service_id' => $priced['service_id'],
                    'quantity' => $priced['quantity'],
                    'unit_price' => $priced['unit_price'],
                    'subtotal' => $priced['subtotal'],
                ]);
            }

            $longestDuration = (int) ($this->order->items()->get()
                ->map(fn ($item) => $item->service?->duration_hours)
                ->filter()
                ->max() ?: 24);

            $this->order->update([
                'total_weight' => $result['total_weight'],
                'total_price' => $result['total'],
                'estimated_done_at' => \Illuminate\Support\Carbon::now()->addHours($longestDuration),
            ]);

            return $service->advance($this->order->refresh(), auth()->user());
        });

        $this->order = $order->load(['customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator']);

        unset($this->isPaid, $this->remainingAmount);

        Flux::toast(variant: 'success', text: 'Pesanan diterima. Total akhir '.Money::rupiah($order->total_price).'.');
    }

    /**
     * Layanan aktif untuk dropdown konfirmasi.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service>
     */
    #[Computed]
    public function services(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Tolak pesanan online dengan alasan wajib.
     */
    public function rejectOrder(OrderStatusService $service): void
    {
        Gate::authorize('cancel', $this->order);

        $validated = $this->validate([
            'rejectReason' => ['required', 'string', 'max:255'],
        ], [], [
            'rejectReason' => 'alasan penolakan',
        ]);

        $this->order = $service->cancel($this->order, auth()->user(), $validated['rejectReason'])
            ->load(['customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator']);

        $this->showRejectDialog = false;
        $this->rejectReason = '';

        Flux::toast(variant: 'success', text: 'Pesanan ditolak.');
    }

    /**
     * Buka form mundurkan status.
     */
    public function openRollbackForm(): void
    {
        Gate::authorize('rollbackStatus', $this->order);

        $this->showRollbackForm = true;
    }

    /**
     * Lanjut ke status berikutnya.
     */
    public function advance(OrderStatusService $service): void
    {
        Gate::authorize('changeStatus', $this->order);

        $next = $this->nextStatus();

        if ($next === null) {
            Flux::toast(variant: 'danger', text: 'Pesanan sudah berada di status akhir.');

            return;
        }

        // Cucian hanya boleh ditandai Diambil jika sudah lunas.
        if ($next === OrderStatus::PickedUp && ! $this->isPaid) {
            Flux::toast(variant: 'warning', text: 'Pesanan belum lunas. Catat pembayaran dulu sebelum menandai Diambil.');

            return;
        }

        try {
            $this->order = $service->advance($this->order, auth()->user())->load([
                'customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator',
            ]);

            Flux::toast(variant: 'success', text: 'Status pesanan diperbarui menjadi '.$this->order->status->label().'.');
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * Mundurkan status (hanya pemilik, catatan wajib).
     */
    public function rollback(OrderStatusService $service): void
    {
        Gate::authorize('rollbackStatus', $this->order);

        try {
            // Mundur satu tahap dari status sekarang.
            $stages = collect($this->timelineStages());
            $currentIndex = $stages->search($this->order->status?->value);
            $target = $currentIndex > 0 ? OrderStatus::from($stages[$currentIndex - 1]) : OrderStatus::Received;

            $this->order = $service->rollback($this->order, $target, auth()->user(), $this->rollbackNote)->load([
                'customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator',
            ]);

            $this->rollbackNote = '';
            $this->showRollbackForm = false;

            Flux::toast(variant: 'success', text: 'Status pesanan dimundurkan ke '.$this->order->status->label().'.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('rollbackNote', $e->errors()['note'][0] ?? 'Catatan wajib diisi.');
        } catch (\RuntimeException | \Illuminate\Auth\Access\AuthorizationException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * Batalkan pesanan (sebelum status Dicuci).
     */
    public function cancel(OrderStatusService $service): void
    {
        Gate::authorize('cancel', $this->order);

        try {
            $this->order = $service->cancel($this->order, auth()->user(), $this->cancelNote !== '' ? $this->cancelNote : null)
                ->load(['customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator']);

            $this->showCancelDialog = false;
            $this->cancelNote = '';

            Flux::toast(variant: 'success', text: 'Pesanan dibatalkan.');
        } catch (\RuntimeException $e) {
            $this->showCancelDialog = false;

            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * Catat pembayaran.
     */
    public function recordPayment(PaymentService $service): void
    {
        Gate::authorize('recordPayment', $this->order);

        $validated = $this->validate([
            'paymentAmount' => ['required', 'integer', 'min:1'],
            'paymentMethod' => ['required', 'in:cash,transfer,qris'],
        ], [], [
            'paymentAmount' => 'jumlah pembayaran',
            'paymentMethod' => 'metode pembayaran',
        ]);

        try {
            $service->record(
                $this->order,
                (int) $validated['paymentAmount'],
                PaymentMethod::from($validated['paymentMethod']),
                auth()->user(),
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $this->paymentAmount = '';

        $this->order = $this->order->refresh()->load([
            'customer', 'items.service', 'statusLogs.changer', 'payments.receiver', 'creator',
        ]);

        unset($this->isPaid, $this->remainingAmount);

        Flux::toast(variant: 'success', text: 'Pembayaran berhasil dicatat.');
    }
};
?>

<div class="mx-auto w-full max-w-5xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-mono text-xl font-semibold text-ink-900">{{ $order->code }}</h1>
                <x-status-badge :status="$order->status" />
                <x-payment-badge :status="$order->payment_status" />
            </div>
            <p class="mt-1 text-sm text-ink-600">
                Dibuat <x-datetime :value="$order->created_at" />
                @if ($order->creator)
                    oleh {{ $order->creator->name }}
                @endif
            </p>
        </div>

        @if (Route::has('admin.pesanan.nota'))
            <flux:button variant="outline" icon="printer" :href="route('admin.pesanan.nota', $order)" class="h-11">
                Cetak Nota
            </flux:button>
        @else
            <flux:button variant="outline" icon="printer" class="h-11" disabled>
                Cetak Nota
            </flux:button>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            {{-- Info pelanggan & pesanan --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">Informasi Pesanan</h2>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-ink-600">Pelanggan</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $order->customer?->name ?? '—' }}</dd>
                        <dd class="text-sm tabular-nums text-ink-600">{{ $order->customer?->phone ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-600">Sumber</dt>
                        <dd class="mt-1 text-ink-900">{{ $order->source?->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-600">Estimasi selesai</dt>
                        <dd class="mt-1 text-ink-900">
                            @if ($order->estimated_done_at)
                                <x-datetime :value="$order->estimated_done_at" />
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-600">Total berat</dt>
                        <dd class="mt-1 tabular-nums text-ink-900">{{ \App\Support\Money::weight((float) $order->total_weight) }}</dd>
                    </div>
                    @if ($order->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-ink-600">Catatan</dt>
                            <dd class="mt-1 text-ink-900">{{ $order->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- Item pesanan --}}
            <section class="overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm">
                <div class="border-b border-ink-300/60 p-5">
                    <h2 class="font-display text-base font-semibold text-ink-900">Item Pesanan</h2>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Layanan</th>
                            <th class="px-5 py-3 font-semibold">Berat/Jumlah</th>
                            <th class="px-5 py-3 font-semibold">Harga</th>
                            <th class="px-5 py-3 text-end font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr class="border-t border-ink-300/60" wire:key="detail-item-{{ $item->id }}">
                                <td class="px-5 py-3 font-medium text-ink-900">{{ $item->service?->name }}</td>
                                <td class="px-5 py-3 tabular-nums text-ink-600">
                                    @if ($item->service?->unit?->value === 'pcs')
                                        {{ (int) $item->quantity }} pcs
                                    @else
                                        {{ \App\Support\Money::weight((float) $item->quantity) }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 tabular-nums text-ink-600">{{ \App\Support\Money::rupiah($item->unit_price) }}</td>
                                <td class="px-5 py-3 text-end tabular-nums font-medium text-ink-900">
                                    {{ \App\Support\Money::rupiah($item->subtotal) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-sm text-ink-600">Belum ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-ink-300/60 bg-primary-50/50">
                            <td colspan="3" class="px-5 py-3 text-end font-semibold text-ink-900">Total</td>
                            <td class="px-5 py-3 text-end font-display text-lg font-semibold text-primary-600">
                                {{ \App\Support\Money::rupiah($order->total_price) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            {{-- Timeline status --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">Riwayat Status</h2>

                <ol class="mt-4 space-y-0">
                    @foreach ($this->timelineStages() as $index => $stage)
                        @php
                            $log = $order->statusLogs->firstWhere('status.value', $stage);
                            $isCurrent = $order->status?->value === $stage;
                            $reachedIndex = collect($this->timelineStages())->search($order->status?->value);
                            $isDone = $reachedIndex !== false && $index < $reachedIndex;
                            $isCancelled = $stage === OrderStatus::Cancelled->value;
                        @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0" wire:key="timeline-{{ $stage }}">
                            {{-- Garis penghubung --}}
                            @if (! $loop->last)
                                <span class="absolute left-[11px] top-6 h-full w-0.5 {{ $isDone ? 'bg-primary-600' : 'bg-ink-300' }}"></span>
                            @endif

                            {{-- Titik --}}
                            <span class="relative z-10 mt-1 flex size-6 items-center justify-center rounded-full border-2
                                {{ $isDone ? 'border-primary-600 bg-primary-600' : ($isCurrent ? 'border-primary-600 bg-white motion-safe:animate-pulse' : 'border-ink-300 bg-white') }}">
                                @if ($isDone)
                                    <flux:icon icon="check" class="size-3.5 text-white" variant="mini" />
                                @elseif ($isCurrent)
                                    <span class="size-2 rounded-full bg-primary-600"></span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="font-medium {{ $isDone || $isCurrent ? 'text-ink-900' : 'text-ink-600' }}">
                                    {{ \App\Support\OrderStatusStyles::for($stage)['label'] }}
                                </p>
                                @if ($log)
                                    <p class="mt-0.5 text-xs text-ink-600">
                                        <x-datetime :value="$log->created_at" />
                                        @if ($log->changer)
                                            · {{ $log->changer->name }}
                                        @endif
                                        @if ($log->note)
                                            · "{{ $log->note }}"
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        {{-- Kolom aksi --}}
        <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
            {{-- Aksi status --}}
            @if ($order->status?->value === \App\Enums\OrderStatus::PendingConfirmation->value)
                <section class="rounded-2xl border-2 border-primary-500 bg-white p-5 shadow-sm">
                    <h2 class="font-display text-base font-semibold text-ink-900">Terima Cucian dan Timbang</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Isi berat aktual setiap item. Total dan estimasi selesai dihitung ulang otomatis.
                    </p>

                    <div class="mt-4 space-y-4">
                        @foreach ($order->items as $item)
                            @php($itemId = $item->id)
                            <div wire:key="confirm-item-{{ $itemId }}">
                                <flux:select wire:model.live="confirmingItems.{{ $itemId }}.service_id" label="Layanan">
                                    @foreach ($this->services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </flux:select>

                                <div class="mt-2">
                                    <flux:input wire:model.live="confirmingItems.{{ $itemId }}.quantity" type="number"
                                        min="0" step="0.5" inputmode="decimal"
                                        label="Berat aktual (kg) / jumlah (pcs)" />
                                </div>

                                <p class="mt-1 text-xs text-ink-600">
                                    Estimasi pelanggan:
                                    @if ($item->service?->unit?->value === 'pcs')
                                        {{ (int) $item->quantity }} pcs
                                    @else
                                        {{ \App\Support\Money::weight((float) $item->quantity) }}
                                    @endif
                                    ({{ $item->service?->name }})
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-ink-300/60 pt-3">
                        <span class="font-semibold text-ink-900">Total baru</span>
                        <span class="font-display text-lg font-semibold text-primary-600">
                            {{ \App\Support\Money::rupiah($this->confirmPricing['total']) }}
                        </span>
                    </div>

                    <flux:button variant="primary" class="mt-4 h-12 w-full" wire:click="confirmOrder"
                        data-test="confirm-order-button">
                        Konfirmasi dan Terima
                    </flux:button>

                    <flux:button variant="ghost" class="mt-2 w-full text-red-600"
                        wire:click="$set('showRejectDialog', true)" data-test="reject-order-button">
                        Tolak
                    </flux:button>
                </section>
            @endif

            @if (! $order->status?->isFinal())
                <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                    <h2 class="font-display text-base font-semibold text-ink-900">Ubah Status</h2>

                    @if ($next = $this->nextStatus())
                        @if ($order->status?->value !== \App\Enums\OrderStatus::PendingConfirmation->value)
                            @if ($next === OrderStatus::PickedUp && ! $this->isPaid)
                            <div class="mt-3 rounded-xl bg-status-ironing p-3 text-sm text-status-ironing-ink">
                                Pesanan belum lunas. Catat pembayaran terlebih dahulu sebelum menandai cucian Diambil.
                            </div>
                        @endif

                        <flux:button variant="primary" class="mt-4 h-12 w-full" icon="arrow-right"
                            wire:click="advance" wire:loading.attr="disabled"
                            :disabled="$next === OrderStatus::PickedUp && ! $this->isPaid"
                            data-test="advance-status-button">
                            Lanjut ke {{ $next->label() }}
                        </flux:button>
                    @endif
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (auth()->user()->isOwner())
                            <flux:button variant="outline" size="sm" icon="arrow-uturn-left" wire:click="openRollbackForm">
                                Mundurkan status
                            </flux:button>
                        @endif

                        @if ($order->status->canBeCancelled())
                            <flux:button variant="ghost" size="sm" class="text-red-600" icon="x-mark"
                                wire:click="$set('showCancelDialog', true)" data-test="cancel-order-button">
                                Batalkan
                            </flux:button>
                        @endif
                    </div>

                    @if ($showRollbackForm)
                        <div class="mt-4 space-y-3 rounded-xl border border-ink-300/70 p-3">
                            <flux:textarea wire:model="rollbackNote" rows="2" label="Alasan mundurkan status (wajib)"
                                placeholder="Contoh: salah input berat" />
                            <div class="flex justify-end gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="$set('showRollbackForm', false)">
                                    Batal
                                </flux:button>
                                <flux:button variant="primary" size="sm" wire:click="rollback"
                                    data-test="confirm-rollback-button">
                                    Mundurkan
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </section>
            @else
                <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                    <h2 class="font-display text-base font-semibold text-ink-900">Status Akhir</h2>
                    <p class="mt-2 text-sm text-ink-600">
                        Pesanan ini sudah {{ strtolower($order->status->label()) }} dan tidak dapat diubah lagi.
                    </p>
                </section>
            @endif

            {{-- Pembayaran --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">Pembayaran</h2>

                <dl class="mt-3 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-600">Total</dt>
                        <dd class="tabular-nums font-medium text-ink-900">{{ \App\Support\Money::rupiah($order->total_price) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-600">Sudah dibayar</dt>
                        <dd class="tabular-nums text-ink-900">{{ \App\Support\Money::rupiah($order->total_price - $this->remainingAmount) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-600">Sisa tagihan</dt>
                        <dd class="tabular-nums font-semibold text-primary-600">{{ \App\Support\Money::rupiah($this->remainingAmount) }}</dd>
                    </div>
                </dl>

                <div class="mt-4 space-y-2">
                    @forelse ($order->payments as $payment)
                        <div class="flex items-center justify-between rounded-xl bg-surface px-3 py-2 text-sm"
                            wire:key="payment-{{ $payment->id }}">
                            <div>
                                <p class="font-medium text-ink-900">{{ $payment->method?->label() }}</p>
                                <p class="text-xs text-ink-600">
                                    <x-datetime :value="$payment->paid_at" />
                                    @if ($payment->receiver)
                                        · {{ $payment->receiver->name }}
                                    @endif
                                </p>
                            </div>
                            <span class="tabular-nums font-medium text-ink-900">{{ \App\Support\Money::rupiah($payment->amount) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-600">Belum ada pembayaran.</p>
                    @endforelse
                </div>

                @if ($this->remainingAmount > 0 && ! $order->status?->isFinal())
                    <form wire:submit="recordPayment" class="mt-4 space-y-3 border-t border-ink-300/60 pt-4">
                        <flux:select wire:model="paymentMethod" label="Metode pembayaran">
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </flux:select>

                        <flux:input wire:model="paymentAmount" type="number" min="1" step="500" inputmode="numeric"
                            label="Jumlah (Rp)" placeholder="{{ $this->remainingAmount }}" />

                        <div class="flex gap-2">
                            <flux:button type="button" variant="ghost" size="sm"
                                wire:click="$set('paymentAmount', '{{ $this->remainingAmount }}')">
                                Lunas
                            </flux:button>
                        </div>

                        <flux:button type="submit" variant="primary" class="h-11 w-full" data-test="record-payment-button">
                            Catat Pembayaran
                        </flux:button>
                    </form>
                @endif
            </section>
        </aside>
    </div>

    {{-- Dialog konfirmasi pembatalan --}}
    <flux:modal name="batalkan-pesanan" wire:model="showCancelDialog" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Batalkan Pesanan?</flux:heading>

            <flux:text>
                Pesanan {{ $order->code }} akan dibatalkan. Tindakan ini tidak bisa diurungkan.
            </flux:text>

            <flux:textarea wire:model="cancelNote" rows="2" label="Alasan pembatalan (opsional)"
                placeholder="Contoh: pelanggan batal mencuci" />

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="outline" x-on:click="$flux.modal('batalkan-pesanan').close()">
                    Tutup
                </flux:button>
                <flux:button variant="danger" wire:click="cancel" data-test="confirm-cancel-button">
                    Batalkan Pesanan
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Dialog tolak pesanan online --}}
    <flux:modal name="tolak-pesanan" wire:model="showRejectDialog" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Tolak Pesanan?</flux:heading>

            <flux:text>
                Pesanan {{ $order->code }} akan ditolak. Alasan wajib diisi agar pelanggan tahu penyebabnya.
            </flux:text>

            <flux:textarea wire:model="rejectReason" rows="2" label="Alasan penolakan (wajib)" required
                placeholder="Contoh: layanan sedang penuh" />

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="outline" x-on:click="$flux.modal('tolak-pesanan').close()">
                    Tutup
                </flux:button>
                <flux:button variant="danger" wire:click="rejectOrder" data-test="confirm-reject-button">
                    Tolak Pesanan
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
