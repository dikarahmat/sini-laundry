<?php

use App\Models\Customer;
use App\Support\Phone;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pelanggan')] class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingCustomerId = null;

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    /**
     * Daftar pelanggan dengan pencarian nama/HP dan paginasi.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    #[Computed]
    public function customers()
    {
        return Customer::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->with('user')
            ->latest('id')
            ->paginate(15);
    }

    /**
     * Buka form tambah pelanggan walk-in.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Buka form ubah pelanggan.
     */
    public function edit(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->address = $customer->address ?? '';

        $this->showForm = true;
    }

    /**
     * Simpan pelanggan baru atau perubahan pelanggan.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Phone::isValid(is_string($value) ? $value : null)) {
                    $fail('Format nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
                }
            }],
            'address' => ['nullable', 'string', 'max:500'],
        ], [], [
            'name' => 'nama pelanggan',
            'phone' => 'nomor HP',
            'address' => 'alamat',
        ]);

        Customer::updateOrCreate(
            ['id' => $this->editingCustomerId],
            [
                'name' => $validated['name'],
                'phone' => Phone::normalize($validated['phone']),
                'address' => $validated['address'] ?? null,
            ],
        );

        $this->showForm = false;
        $this->resetForm();
        unset($this->customers);

        Flux::toast(variant: 'success', text: 'Data pelanggan berhasil disimpan.');
    }

    /**
     * Reset pencarian ke halaman pertama.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Kembalikan form ke keadaan awal.
     */
    private function resetForm(): void
    {
        $this->editingCustomerId = null;
        $this->name = '';
        $this->phone = '';
        $this->address = '';
        $this->resetValidation();
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Pelanggan" description="Kelola data pelanggan, termasuk pelanggan walk-in tanpa akun.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create" class="h-11">
                Tambah Pelanggan
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
            placeholder="Cari nama atau nomor HP pelanggan..." class="max-w-md" />
    </div>

    {{-- Tabel desktop --}}
    <div class="hidden overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm md:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Nomor HP</th>
                    <th class="px-4 py-3 font-semibold">Alamat</th>
                    <th class="px-4 py-3 font-semibold">Akun</th>
                    <th class="px-4 py-3 text-end font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->customers as $customer)
                    @php($customerId = $customer->id)
                    <tr class="border-t border-ink-300/60" wire:key="customer-desktop-{{ $customerId }}">
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $customer->name }}</td>
                        <td class="px-4 py-3 tabular-nums text-ink-600">{{ $customer->phone }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-ink-600">{{ $customer->address ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($customer->user_id)
                                <span class="rounded-full bg-status-received px-2 py-0.5 text-xs font-medium text-status-received-ink">Terhubung</span>
                            @else
                                <span class="rounded-full bg-status-picked-up px-2 py-0.5 text-xs font-medium text-status-picked-up-ink">Walk-in</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <flux:button size="sm" variant="outline" icon="pencil" wire:click="edit({{ $customerId }})">
                                Ubah
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10">
                            <x-empty-state icon="users" title="Belum ada pelanggan"
                                description="Tambahkan pelanggan walk-in atau tunggu pelanggan mendaftar sendiri." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu HP --}}
    <div class="grid gap-4 md:hidden">
        @forelse ($this->customers as $customer)
            @php($customerId = $customer->id)
            <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm" wire:key="customer-mobile-{{ $customerId }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-base font-semibold text-ink-900">{{ $customer->name }}</p>
                        <p class="mt-1 text-xs tabular-nums text-ink-600">{{ $customer->phone }}</p>
                    </div>
                    @if ($customer->user_id)
                        <span class="rounded-full bg-status-received px-2 py-0.5 text-xs font-medium text-status-received-ink">Terhubung</span>
                    @else
                        <span class="rounded-full bg-status-picked-up px-2 py-0.5 text-xs font-medium text-status-picked-up-ink">Walk-in</span>
                    @endif
                </div>

                <p class="mt-2 text-xs text-ink-600">{{ $customer->address ?? 'Alamat belum diisi' }}</p>

                <div class="mt-4">
                    <flux:button size="sm" variant="outline" icon="pencil" wire:click="edit({{ $customerId }})">
                        Ubah
                    </flux:button>
                </div>
            </div>
        @empty
            <x-empty-state icon="users" title="Belum ada pelanggan"
                description="Tambahkan pelanggan walk-in atau tunggu pelanggan mendaftar sendiri." />
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->customers->links() }}
    </div>

    {{-- Form tambah/ubah pelanggan --}}
    <flux:modal name="pelanggan-form" wire:model="showForm" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingCustomerId ? 'Ubah Pelanggan' : 'Tambah Pelanggan' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="name" label="Nama lengkap" type="text" required placeholder="Contoh: Rina" />

                <flux:input wire:model="phone" label="Nomor HP" type="tel" required inputmode="numeric"
                    placeholder="08xxxxxxxxxx" />

                <flux:textarea wire:model="address" label="Alamat (opsional)" rows="3"
                    placeholder="Alamat pelanggan, opsional" />

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button type="button" variant="outline" x-on:click="$flux.modal('pelanggan-form').close()">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" data-test="save-customer-button">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
