<?php

use App\Enums\ServiceUnit;
use App\Models\Service;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Layanan & Harga')] class extends Component {
    public bool $showForm = false;

    public ?int $editingServiceId = null;

    public string $name = '';

    public string $unit = 'kg';

    public string $price = '';

    public string $duration_hours = '';

    public bool $is_express = false;

    public bool $is_active = true;

    /**
     * Daftar layanan, terbaru dulu.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Service>
     */
    #[Computed]
    public function services()
    {
        return Service::query()->latest('id')->get();
    }

    /**
     * Buka form tambah layanan.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Buka form ubah layanan.
     */
    public function edit(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->name = $service->name;
        $this->unit = $service->unit->value;
        $this->price = (string) $service->price;
        $this->duration_hours = (string) $service->duration_hours;
        $this->is_express = $service->is_express;
        $this->is_active = $service->is_active;

        $this->showForm = true;
    }

    /**
     * Simpan layanan baru atau perubahan layanan.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'in:kg,pcs'],
            'price' => ['required', 'integer', 'min:100'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'is_express' => ['boolean'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => 'nama layanan',
            'unit' => 'satuan',
            'price' => 'harga',
            'duration_hours' => 'estimasi durasi',
            'is_express' => 'layanan express',
            'is_active' => 'status aktif',
        ]);

        if ($validated['price'] < 100) {
            $this->addError('price', 'Harga harus lebih dari 0 rupiah.');
        }

        Service::updateOrCreate(
            ['id' => $this->editingServiceId],
            [
                'name' => $this->name,
                'unit' => ServiceUnit::from($validated['unit']),
                'price' => (int) $this->price,
                'duration_hours' => (int) $this->duration_hours,
                'is_express' => $this->is_express,
                'is_active' => $this->is_active,
            ],
        );

        $this->showForm = false;
        $this->resetForm();
        unset($this->services);

        Flux::toast(variant: 'success', text: 'Layanan berhasil disimpan.');
    }

    /**
     * Aktif/nonaktifkan layanan (tidak menghapus data).
     */
    public function toggleActive(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        $service->update(['is_active' => ! $service->is_active]);

        unset($this->services);

        Flux::toast(
            variant: 'success',
            text: $service->is_active ? 'Layanan diaktifkan.' : 'Layanan dinonaktifkan.',
        );
    }

    /**
     * Kembalikan form ke keadaan awal.
     */
    private function resetForm(): void
    {
        $this->editingServiceId = null;
        $this->name = '';
        $this->unit = ServiceUnit::Kilogram->value;
        $this->price = '';
        $this->duration_hours = '';
        $this->is_express = false;
        $this->is_active = true;
        $this->resetValidation();
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Layanan & Harga" description="Kelola layanan laundry dan harga yang dibebankan ke pelanggan.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create" class="h-11">
                Tambah Layanan
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Tabel desktop --}}
    <div class="hidden overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm md:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama Layanan</th>
                    <th class="px-4 py-3 font-semibold">Satuan</th>
                    <th class="px-4 py-3 font-semibold">Harga</th>
                    <th class="px-4 py-3 font-semibold">Estimasi</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-end font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->services as $service)
                    @php($serviceId = $service->id)
                    <tr class="border-t border-ink-300/60" wire:key="service-desktop-{{ $service->id }}">
                        <td class="px-4 py-3 font-medium text-ink-900">
                            {{ $service->name }}
                            @if ($service->is_express)
                                <span class="ms-2 rounded-full bg-status-ironing px-2 py-0.5 text-xs font-medium text-status-ironing-ink">Express</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-600">{{ $service->unit->label() }}</td>
                        <td class="px-4 py-3 tabular-nums text-ink-900">{{ \App\Support\Money::rupiah($service->price) }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $service->duration_hours }} jam</td>
                        <td class="px-4 py-3">
                            @if ($service->is_active)
                                <span class="rounded-full bg-status-done px-2 py-0.5 text-xs font-medium text-status-done-ink">Aktif</span>
                            @else
                                <span class="rounded-full bg-status-cancelled px-2 py-0.5 text-xs font-medium text-status-cancelled-ink">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="outline" icon="pencil" wire:click="edit({{ $serviceId }})">
                                    Ubah
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $serviceId }})">
                                    {{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10">
                            <x-empty-state icon="sparkles" title="Belum ada layanan"
                                description="Tambahkan layanan pertama, misalnya Cuci + Setrika." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu HP --}}
    <div class="grid gap-4 md:hidden">
        @forelse ($this->services as $service)
            @php($serviceId = $service->id)
            <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm" wire:key="service-mobile-{{ $service->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-base font-semibold text-ink-900">{{ $service->name }}</p>
                        <p class="mt-1 text-xs text-ink-600">{{ $service->unit->label() }} &middot; estimasi {{ $service->duration_hours }} jam</p>
                    </div>
                    @if ($service->is_active)
                        <span class="rounded-full bg-status-done px-2 py-0.5 text-xs font-medium text-status-done-ink">Aktif</span>
                    @else
                        <span class="rounded-full bg-status-cancelled px-2 py-0.5 text-xs font-medium text-status-cancelled-ink">Nonaktif</span>
                    @endif
                </div>

                <p class="mt-3 font-display text-lg font-semibold text-primary-600">{{ \App\Support\Money::rupiah($service->price) }}</p>

                <div class="mt-4 flex items-center gap-2">
                    <flux:button size="sm" variant="outline" icon="pencil" wire:click="edit({{ $serviceId }})">
                        Ubah
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $serviceId }})">
                        {{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </flux:button>
                </div>
            </div>
        @empty
            <x-empty-state icon="sparkles" title="Belum ada layanan"
                description="Tambahkan layanan pertama, misalnya Cuci + Setrika." />
        @endforelse
    </div>

    {{-- Form tambah/ubah layanan --}}
    <flux:modal name="layanan-form" wire:model="showForm" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingServiceId ? 'Ubah Layanan' : 'Tambah Layanan' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="name" label="Nama layanan" type="text" required placeholder="Contoh: Cuci + Setrika" />

                <flux:select wire:model="unit" label="Satuan" required>
                    <flux:select.option value="kg">Kilogram (kg)</flux:select.option>
                    <flux:select.option value="pcs">Satuan (pcs)</flux:select.option>
                </flux:select>

                <flux:input wire:model="price" label="Harga (Rp)" type="number" min="100" step="100" required
                    inputmode="numeric" placeholder="Contoh: 8000" />

                <flux:input wire:model="duration_hours" label="Estimasi selesai (jam)" type="number" min="1" max="720" required
                    inputmode="numeric" placeholder="Contoh: 48" />

                <flux:checkbox wire:model="is_express" label="Layanan express (tambahan biaya dari pengaturan)" />

                <flux:checkbox wire:model="is_active" label="Layanan aktif (bisa dipilih saat membuat pesanan)" />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button type="button" variant="outline" x-on:click="$flux.modal('layanan-form').close()">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" data-test="save-service-button">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
