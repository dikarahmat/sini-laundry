<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderCodeGenerator;
use App\Services\OrderPricingService;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pesan Laundry')] class extends Component {
    /**
     * @var array<int, array{service_id: string, quantity: string}>
     */
    public array $rows = [];

    public string $pickupDate = '';

    public string $notes = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Customer::ensureForUser(auth()->user());

        $this->rows[] = ['service_id' => '', 'quantity' => ''];
        $this->pickupDate = Carbon::now()->addDay()->format('Y-m-d');
    }

    /**
     * Layanan aktif.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Service>
     */
    #[Computed]
    public function services()
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Perkiraan harga lewat OrderPricingService.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, total_weight: float}
     */
    #[Computed]
    public function pricing(): array
    {
        $items = array_filter($this->rows, fn (array $row): bool => $row['service_id'] !== '' && (float) $row['quantity'] > 0);

        if ($items === []) {
            return ['items' => [], 'total' => 0, 'total_weight' => 0.0];
        }

        $items = array_map(fn (array $row): array => [
            'service_id' => (int) $row['service_id'],
            'quantity' => (float) $row['quantity'],
        ], $items);

        return app(OrderPricingService::class)->calculate($items);
    }

    /**
     * Tambah baris layanan.
     */
    public function addRow(): void
    {
        $this->rows[] = ['service_id' => '', 'quantity' => ''];
    }

    /**
     * Hapus baris layanan.
     */
    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);

        if ($this->rows === []) {
            $this->rows[] = ['service_id' => '', 'quantity' => ''];
        } else {
            $this->rows = array_values($this->rows);
        }
    }

    /**
     * Simpan pesanan online (status Menunggu Konfirmasi).
     */
    public function save(OrderCodeGenerator $codes, OrderPricingService $pricing): mixed
    {
        $validated = $this->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'rows.*.quantity' => ['required', 'numeric', 'gt:0'],
            'pickupDate' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'rows.required' => 'Pilih minimal satu layanan.',
            'rows.*.service_id.required' => 'Pilih layanan untuk setiap baris.',
            'rows.*.quantity.required' => 'Isi estimasi berat (kg) atau jumlah (pcs).',
            'rows.*.quantity.gt' => 'Estimasi berat harus lebih dari 0 kg.',
            'pickupDate.required' => 'Pilih tanggal rencana antar ke toko.',
            'pickupDate.after_or_equal' => 'Tanggal antar tidak boleh kurang dari hari ini.',
        ], [
            'rows' => 'layanan',
            'pickupDate' => 'tanggal antar',
        ]);

        $order = DB::transaction(function () use ($codes, $pricing, $validated): Order {
            $customer = Customer::ensureForUser(auth()->user());

            $result = $pricing->calculate($validated['rows']);

            $order = Order::create([
                'code' => $codes->generate(),
                'customer_id' => $customer->id,
                'created_by' => null, // pesanan online dibuat pelanggan
                'source' => OrderSource::Online,
                'status' => OrderStatus::PendingConfirmation,
                'payment_status' => PaymentStatus::Unpaid,
                'total_weight' => $result['total_weight'],
                'total_price' => $result['total'],
                'notes' => $validated['notes'] ?? null,
                'estimated_done_at' => Carbon::parse($validated['pickupDate']),
            ]);

            foreach ($result['items'] as $item) {
                $order->items()->create([
                    'service_id' => $item['service_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $order->statusLogs()->create([
                'status' => OrderStatus::PendingConfirmation,
                'changed_by' => null,
                'note' => 'Pesanan online dibuat pelanggan',
            ]);

            return $order;
        });

        Flux::toast(variant: 'success', text: 'Pesanan '.$order->code.' berhasil dibuat. Menunggu konfirmasi kasir.');

        return redirect()->route('pesanan.show', $order);
    }
};
?>

<div class="mx-auto w-full max-w-lg space-y-6">
    <x-page-header title="Pesan Laundry"
        description="Pilih layanan, isi estimasi berat, lalu pesan. Harga final menunggu penimbangan di toko." />

    <div class="space-y-4">
        @foreach ($rows as $index => $row)
            <div class="rounded-2xl border border-ink-300 bg-white p-4 shadow-sm" wire:key="online-row-{{ $index }}">
                <flux:select wire:model.live="rows.{{ $index }}.service_id" label="Layanan {{ $index + 1 }}">
                    <option value="">Pilih layanan...</option>
                    @foreach ($this->services as $service)
                        <option value="{{ $service->id }}">
                            {{ $service->name }} — {{ \App\Support\Money::rupiah($service->price) }}/{{ $service->unit->label() }}
                        </option>
                    @endforeach
                </flux:select>

                <div class="mt-3 flex items-end gap-3">
                    <flux:input wire:model.live="rows.{{ $index }}.quantity" type="number" min="0" step="0.5"
                        inputmode="decimal" label="Estimasi berat (kg) / jumlah (pcs)" placeholder="0" class="flex-1" />

                    <flux:button variant="ghost" size="sm" icon="trash" class="mb-1 text-red-600"
                        wire:click="removeRow({{ $index }})" aria-label="Hapus baris" />
                </div>

                @error("rows.{$index}.service_id")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error("rows.{$index}.quantity")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        @error('rows')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <flux:button variant="outline" icon="plus" wire:click="addRow" class="h-11 w-full">
            Tambah Layanan
        </flux:button>
    </div>

    {{-- Perkiraan harga --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
        <h2 class="font-display text-base font-semibold text-ink-900">Perkiraan Harga</h2>

        <div class="mt-3 space-y-2 text-sm">
            @forelse ($this->pricing['items'] as $item)
                <div class="flex items-center justify-between">
                    <span class="text-ink-600">{{ $item['service_name'] }}</span>
                    <span class="tabular-nums text-ink-900">{{ \App\Support\Money::rupiah($item['subtotal']) }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-600">Belum ada layanan dipilih.</p>
            @endforelse

            @if ($this->pricing['total'] > 0)
                <div class="flex items-center justify-between border-t border-ink-300/60 pt-3">
                    <span class="font-semibold text-ink-900">Perkiraan total</span>
                    <span class="font-display text-lg font-semibold text-primary-600">
                        {{ \App\Support\Money::rupiah($this->pricing['total']) }}
                    </span>
                </div>
            @endif
        </div>

        <p class="mt-3 rounded-xl bg-status-pending-confirmation p-3 text-xs text-status-pending-confirmation-ink">
            Ini hanya perkiraan. Harga final dihitung dari berat aktual setelah cucian ditimbang kasir di toko.
        </p>
    </section>

    {{-- Jadwal dan catatan --}}
    <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
        <div class="space-y-4">
            <flux:input wire:model="pickupDate" type="date" label="Rencana antar ke toko" required />

            <flux:textarea wire:model="notes" rows="3" label="Catatan (opsional)"
                placeholder="Contoh: pisahkan pakaian putih" />
        </div>
    </section>

    <flux:button variant="primary" class="h-12 w-full" wire:click="save" data-test="submit-online-order-button"
        wire:loading.attr="disabled">
        Buat Pesanan
    </flux:button>
</div>
