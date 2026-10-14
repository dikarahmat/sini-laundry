<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderCodeGenerator;
use App\Services\OrderPricingService;
use App\Services\PaymentService;
use App\Support\Phone;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pesanan Baru')] class extends Component {
    public string $customerSearch = '';

    public ?int $selectedCustomerId = null;

    /**
     * Baris item pesanan.
     *
     * @var array<int, array{service_id: string, quantity: string}>
     */
    public array $rows = [];

    public bool $showNewCustomerForm = false;

    public string $newCustomerName = '';

    public string $newCustomerPhone = '';

    public string $notes = '';

    public string $estimatedDoneAt = '';

    public bool $estimatedDoneTouched = false;

    public string $paymentAmount = '';

    public string $paymentMethod = 'cash';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->rows[] = ['service_id' => '', 'quantity' => ''];
    }

    /**
     * Layanan aktif untuk dipilih di form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Service>
     */
    #[Computed]
    public function services()
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Hasil pencarian pelanggan (nama atau nomor HP).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Customer>
     */
    #[Computed]
    public function customerResults()
    {
        if (strlen(trim($this->customerSearch)) < 2) {
            return collect();
        }

        $keyword = trim($this->customerSearch);

        return Customer::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', '%'.$keyword.'%')
                ->orWhere('phone', 'like', '%'.$keyword.'%'))
            ->limit(6)
            ->get();
    }

    /**
     * Pelanggan yang dipilih saat ini.
     */
    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        return $this->selectedCustomerId !== null
            ? Customer::find($this->selectedCustomerId)
            : null;
    }

    /**
     * Hasil perhitungan harga lewat OrderPricingService.
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
     * Estimasi selesai otomatis dari durasi layanan terpanjang yang dipilih.
     */
    #[Computed]
    public function suggestedDoneAt(): ?Carbon
    {
        $durations = collect($this->rows)
            ->filter(fn (array $row): bool => $row['service_id'] !== '')
            ->map(fn (array $row): ?int => Service::find((int) $row['service_id'])?->duration_hours)
            ->filter()
            ->all();

        if ($durations === []) {
            return null;
        }

        return Carbon::now()->addHours(max($durations));
    }

    /**
     * Nilai input estimasi selesai: isi otomatis kalau belum diubah pengguna.
     */
    public function estimatedDoneAtInput(): string
    {
        if ($this->estimatedDoneAt !== '') {
            return $this->estimatedDoneAt;
        }

        return $this->suggestedDoneAt?->format('Y-m-d\TH:i') ?? '';
    }

    /**
     * Pilih pelanggan dari hasil pencarian.
     */
    public function selectCustomer(int $customerId): void
    {
        $this->selectedCustomerId = $customerId;
        $this->customerSearch = '';
    }

    /**
     * Simpan pelanggan walk-in baru langsung dari halaman ini.
     */
    public function createWalkInCustomer(): void
    {
        $validated = $this->validate([
            'newCustomerName' => ['required', 'string', 'max:120'],
            'newCustomerPhone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Phone::isValid(is_string($value) ? $value : null)) {
                    $fail('Format nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
                }
            }],
        ], [], [
            'newCustomerName' => 'nama pelanggan',
            'newCustomerPhone' => 'nomor HP',
        ]);

        $customer = Customer::create([
            'name' => $validated['newCustomerName'],
            'phone' => Phone::normalize($validated['newCustomerPhone']),
        ]);

        $this->selectedCustomerId = $customer->id;
        $this->showNewCustomerForm = false;
        $this->newCustomerName = '';
        $this->newCustomerPhone = '';

        Flux::toast(variant: 'success', text: 'Pelanggan baru ditambahkan.');
    }

    /**
     * Tambah baris item.
     */
    public function addRow(): void
    {
        $this->rows[] = ['service_id' => '', 'quantity' => ''];
    }

    /**
     * Hapus baris item.
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
     * Simpan pesanan walk-in lengkap dengan item, log status, dan pembayaran awal.
     */
    public function save(OrderCodeGenerator $codes, OrderPricingService $pricing, PaymentService $payments): mixed
    {
        $validated = $this->validate([
            'selectedCustomerId' => ['required', 'integer', Rule::exists('customers', 'id')],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'rows.*.quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'estimatedDoneAt' => ['nullable', 'date'],
            'paymentAmount' => ['nullable', 'integer', 'min:0'],
            'paymentMethod' => ['nullable', 'in:cash,transfer,qris'],
        ], [
            'selectedCustomerId.required' => 'Pilih pelanggan terlebih dahulu.',
            'rows.required' => 'Tambahkan minimal satu item pesanan.',
            'rows.*.service_id.required' => 'Pilih layanan untuk setiap baris.',
            'rows.*.quantity.required' => 'Isi berat (kg) atau jumlah (pcs) untuk setiap baris.',
            'rows.*.quantity.gt' => 'Berat harus lebih dari 0 kg.',
            'paymentAmount.min' => 'Jumlah pembayaran tidak boleh negatif.',
        ], [
            'rows' => 'item pesanan',
            'rows.*.service_id' => 'layanan',
            'rows.*.quantity' => 'berat',
            'paymentAmount' => 'pembayaran awal',
        ]);

        $order = DB::transaction(function () use ($codes, $pricing, $payments, $validated): Order {
            /** @var \App\Models\User $actor */
            $actor = auth()->user();

            $result = $pricing->calculate($validated['rows']);

            $order = Order::create([
                'code' => $codes->generate(),
                'customer_id' => $validated['selectedCustomerId'],
                'created_by' => $actor->id,
                'source' => OrderSource::WalkIn,
                'status' => OrderStatus::Received,
                'payment_status' => PaymentStatus::Unpaid,
                'total_weight' => $result['total_weight'],
                'total_price' => $result['total'],
                'notes' => $validated['notes'] ?? null,
                'estimated_done_at' => $this->estimatedDoneAt !== ''
                    ? Carbon::parse($this->estimatedDoneAt)
                    : $this->suggestedDoneAt,
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
                'status' => OrderStatus::Received,
                'changed_by' => $actor->id,
                'note' => 'Pesanan diterima kasir',
            ]);

            $amount = (int) ($validated['paymentAmount'] ?? 0);

            if ($amount > 0) {
                $payments->record(
                    $order,
                    $amount,
                    PaymentMethod::from($validated['paymentMethod'] ?? 'cash'),
                    $actor,
                );
            }

            return $order;
        });

        Flux::toast(variant: 'success', text: 'Pesanan '.$order->code.' berhasil disimpan.');

        return redirect()->route('admin.pesanan.index');
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Pesanan Baru"
        description="Catat pesanan walk-in: pilih pelanggan, pilih layanan, sistem menghitung total otomatis." />

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-6">
            {{-- Pilih pelanggan --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">1. Pelanggan</h2>

                @if ($this->selectedCustomer)
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-primary-50 p-4">
                        <div>
                            <p class="font-medium text-ink-900">{{ $this->selectedCustomer->name }}</p>
                            <p class="text-sm tabular-nums text-ink-600">{{ $this->selectedCustomer->phone }}</p>
                        </div>
                        <flux:button size="sm" variant="ghost" wire:click="$set('selectedCustomerId', null)">
                            Ganti
                        </flux:button>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        <flux:input wire:model.live.debounce.300ms="customerSearch" icon="magnifying-glass"
                            label="Cari pelanggan (nama atau nomor HP)"
                            placeholder="Ketik minimal 2 karakter..." />

                        @if (strlen(trim($customerSearch)) >= 2 && $this->customerResults->isNotEmpty())
                            <div class="divide-y divide-ink-300/60 rounded-xl border border-ink-300">
                                @foreach ($this->customerResults as $customer)
                                    @php($customerId = $customer->id)
                                    <button type="button" wire:click="selectCustomer({{ $customerId }})"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-start transition hover:bg-primary-50"
                                        wire:key="search-customer-{{ $customerId }}">
                                        <span class="font-medium text-ink-900">{{ $customer->name }}</span>
                                        <span class="text-sm tabular-nums text-ink-600">{{ $customer->phone }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif (strlen(trim($customerSearch)) >= 2)
                            <p class="text-sm text-ink-600">Pelanggan tidak ditemukan. Tambahkan sebagai pelanggan baru.</p>
                        @endif

                        <flux:button variant="outline" icon="plus" wire:click="$set('showNewCustomerForm', true)"
                            class="h-11">
                            Pelanggan Baru
                        </flux:button>
                    </div>
                @endif

                @error('selectedCustomerId')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            {{-- Item pesanan --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">2. Item Pesanan</h2>

                <div class="mt-4 space-y-4">
                    @foreach ($rows as $index => $row)
                        @php($rowKey = 'row-'.$index)
                        <div class="rounded-xl border border-ink-300/70 p-4" wire:key="item-{{ $rowKey }}">
                            <div class="grid gap-3 sm:grid-cols-[1fr_9rem_auto] sm:items-end">
                                <flux:select wire:model.live="rows.{{ $index }}.service_id" label="Layanan">
                                    <option value="">Pilih layanan...</option>
                                    @foreach ($this->services as $service)
                                        <option value="{{ $service->id }}">
                                            {{ $service->name }} — {{ \App\Support\Money::rupiah($service->price) }}/{{ $service->unit->label() }}
                                        </option>
                                    @endforeach
                                </flux:select>

                                <flux:input wire:model.live="rows.{{ $index }}.quantity" type="number" min="0" step="0.5"
                                    inputmode="decimal" label="Berat (kg) / Jumlah (pcs)" placeholder="0" />

                                <flux:button variant="ghost" size="sm" icon="trash" class="mb-1 text-red-600"
                                    wire:click="removeRow({{ $index }})" aria-label="Hapus baris" />
                            </div>

                            @error("rows.{$index}.service_id")
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error("rows.{$index}.quantity")
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            @php($item = collect($this->pricing['items'])->firstWhere('service_id', (int) ($row['service_id'] ?: 0)))
                            @if ($item)
                                @php($serviceModel = \App\Models\Service::find($item['service_id']))
                                <p class="mt-2 text-sm text-ink-600">
                                    Berat dibulatkan <strong>{{ \App\Support\Money::weight($item['quantity']) }}</strong>
                                    × {{ \App\Support\Money::rupiah($item['unit_price']) }} {{ $serviceModel?->unit->label() }}
                                    = <strong>{{ \App\Support\Money::rupiah($item['subtotal']) }}</strong>
                                </p>
                            @endif
                        </div>
                    @endforeach

                    @error('rows')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <flux:button variant="outline" icon="plus" wire:click="addRow" class="h-11">
                        Tambah Item
                    </flux:button>
                </div>
            </section>

            {{-- Catatan dan estimasi --}}
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">3. Catatan & Estimasi</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:textarea wire:model.blur="notes" rows="3" label="Catatan pesanan (opsional)"
                        placeholder="Contoh: pisahkan baju putih" />

                    <div>
                        <flux:input wire:model.blur="estimatedDoneAt" type="datetime-local"
                            label="Estimasi selesai (bisa diubah)" />
                        <p class="mt-1 text-xs text-ink-600">
                            Otomatis dari durasi layanan terpanjang.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        {{-- Ringkasan --}}
        <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">Ringkasan</h2>

                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($this->pricing['items'] as $summaryItem)
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-ink-600">
                                {{ $summaryItem['service_name'] }}
                                <span class="block text-xs">{{ \App\Support\Money::weight($summaryItem['quantity']) }}</span>
                            </span>
                            <span class="tabular-nums text-ink-900">
                                {{ \App\Support\Money::rupiah($summaryItem['subtotal']) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-600">Belum ada item.</p>
                    @endforelse

                    <div class="flex items-center justify-between border-t border-ink-300/60 pt-3">
                        <span class="font-semibold text-ink-900">Total</span>
                        <span class="font-display text-xl font-semibold text-primary-600">
                            {{ \App\Support\Money::rupiah($this->pricing['total']) }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm">
                <h2 class="font-display text-base font-semibold text-ink-900">Pembayaran Awal (opsional)</h2>

                <div class="mt-4 space-y-3">
                    <flux:select wire:model="paymentMethod" label="Metode">
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="qris">QRIS</option>
                    </flux:select>

                    <flux:input wire:model.live="paymentAmount" type="number" min="0" step="500" inputmode="numeric"
                        label="Jumlah (Rp)" placeholder="0" />
                </div>
            </section>

            <flux:button variant="primary" class="h-12 w-full" wire:click="save" data-test="save-order-button"
                wire:loading.attr="disabled">
                Simpan Pesanan
            </flux:button>
        </aside>
    </div>

    {{-- Modal pelanggan baru --}}
    <flux:modal name="pelanggan-baru" wire:model="showNewCustomerForm" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Pelanggan Baru (Walk-in)</flux:heading>

            <form wire:submit="createWalkInCustomer" class="space-y-4">
                <flux:input wire:model="newCustomerName" label="Nama lengkap" type="text" required
                    placeholder="Contoh: Pak Budi" />

                <flux:input wire:model="newCustomerPhone" label="Nomor HP" type="tel" required inputmode="numeric"
                    placeholder="08xxxxxxxxxx" />

                <div class="flex items-center justify-end gap-3">
                    <flux:button type="button" variant="outline"
                        x-on:click="$flux.modal('pelanggan-baru').close()">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" data-test="save-new-customer-button">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
