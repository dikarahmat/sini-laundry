<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Phone;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengguna')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public bool $showCreateForm = false;

    public string $newName = '';

    public string $newEmail = '';

    public string $newPhone = '';

    public string $newPassword = '';

    public bool $showResetForm = false;

    public ?int $resettingUserId = null;

    public string $resetPassword = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        abort_unless(auth()->user()->isOwner(), 403);
    }

    /**
     * Daftar pengguna dengan filter peran dan pencarian nama/email.
     */
    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->role !== '', function ($query): void {
                $query->where('role', $this->role);
            })
            ->when($this->search !== '', function ($query): void {
                $keyword = trim($this->search);

                $query->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%');
                });
            })
            ->latest('id')
            ->paginate(15);
    }

    /**
     * Simpan akun admin baru.
     */
    public function createAdmin(): void
    {
        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:120'],
            'newEmail' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'newPhone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Phone::isValid(is_string($value) ? $value : null)) {
                    $fail('Format nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
                }
            }],
            'newPassword' => ['required', 'string', 'min:8'],
        ], [], [
            'newName' => 'nama',
            'newEmail' => 'email',
            'newPhone' => 'nomor HP',
            'newPassword' => 'password awal',
        ]);

        User::create([
            'name' => $validated['newName'],
            'email' => $validated['newEmail'],
            'phone' => Phone::normalize($validated['newPhone']),
            'role' => Role::Admin,
            'password' => $validated['newPassword'],
            'is_active' => true,
        ]);

        $this->resetCreateForm();

        unset($this->users);

        Flux::toast(variant: 'success', text: 'Akun admin berhasil dibuat.');
    }

    /**
     * Atur ulang password admin.
     */
    public function resetPassword(): void
    {
        $user = User::findOrFail($this->resettingUserId);

        // Owner tidak boleh mengubah dirinya sendiri lewat halaman ini.
        abort_if($user->is(auth()->user()), 403);
        abort_unless($user->role !== Role::Owner, 403);

        $validated = $this->validate([
            'resetPassword' => ['required', 'string', 'min:8'],
        ], [], [
            'resetPassword' => 'password baru',
        ]);

        $user->update(['password' => $validated['resetPassword']]);

        $this->resetResetForm();

        Flux::toast(variant: 'success', text: 'Password '.$user->name.' berhasil diperbarui.');
    }

    /**
     * Aktif/nonaktifkan akun (hanya admin lain; owner tidak bisa menonaktifkan diri sendiri).
     */
    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->is(auth()->user()), 403);
        abort_unless($user->role === Role::Admin, 403);

        $user->update(['is_active' => ! $user->is_active]);

        unset($this->users);

        Flux::toast(
            variant: 'success',
            text: $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.',
        );
    }

    /**
     * Buka form reset password.
     */
    public function openResetForm(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->is(auth()->user()), 403);

        $this->resettingUserId = $user->id;
        $this->resetPassword = '';
        $this->showResetForm = true;
    }

    /**
     * Reset pencarian ke halaman pertama.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * Reset form tambah pengguna.
     */
    private function resetCreateForm(): void
    {
        $this->showCreateForm = false;
        $this->newName = '';
        $this->newEmail = '';
        $this->newPhone = '';
        $this->newPassword = '';
        $this->resetValidation();
    }

    /**
     * Reset form ubah password.
     */
    private function resetResetForm(): void
    {
        $this->showResetForm = false;
        $this->resettingUserId = null;
        $this->resetPassword = '';
        $this->resetValidation();
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <x-page-header title="Pengguna" description="Kelola akun admin dan pemilik.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateForm', true)" class="h-11">
                Tambah Admin
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" label="Cari"
            placeholder="Nama atau email" />

        <flux:select wire:model.live="role" label="Peran">
            <option value="">Semua peran</option>
            <option value="customer">Pelanggan</option>
            <option value="admin">Admin</option>
            <option value="owner">Pemilik</option>
        </flux:select>
    </div>

    {{-- Tabel desktop --}}
    <div class="hidden overflow-hidden rounded-2xl border border-ink-300 bg-white shadow-sm lg:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-primary-50 text-xs uppercase tracking-wide text-ink-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Nomor HP</th>
                    <th class="px-4 py-3 font-semibold">Peran</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-end font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->users as $user)
                    @php($userId = $user->id)
                    <tr class="border-t border-ink-300/60" wire:key="user-desktop-{{ $userId }}">
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 tabular-nums text-ink-600">{{ $user->phone ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700">
                                {{ $user->role?->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="rounded-full bg-status-done px-2 py-0.5 text-xs font-medium text-status-done-ink">Aktif</span>
                            @else
                                <span class="rounded-full bg-status-cancelled px-2 py-0.5 text-xs font-medium text-status-cancelled-ink">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="outline" icon="key" wire:click="openResetForm({{ $userId }})">
                                    Reset Password
                                </flux:button>

                                @if (! $user->is(auth()->user()) && $user->role === \App\Enums\Role::Admin)
                                    <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $userId }})">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10">
                            <x-empty-state icon="users" title="Tidak ada pengguna"
                                description="Coba ubah filter atau kata pencarian." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Kartu HP --}}
    <div class="grid gap-4 lg:hidden">
        @forelse ($this->users as $user)
            @php($userId = $user->id)
            <div class="rounded-2xl border border-ink-300 bg-white p-5 shadow-sm" wire:key="user-mobile-{{ $userId }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-ink-900">{{ $user->name }}</p>
                        <p class="text-xs text-ink-600">{{ $user->email }}</p>
                    </div>
                    @if ($user->is_active)
                        <span class="rounded-full bg-status-done px-2 py-0.5 text-xs font-medium text-status-done-ink">Aktif</span>
                    @else
                        <span class="rounded-full bg-status-cancelled px-2 py-0.5 text-xs font-medium text-status-cancelled-ink">Nonaktif</span>
                    @endif
                </div>

                <p class="mt-2 text-xs text-ink-600">
                    {{ $user->role?->label() }} · {{ $user->phone ?? '-' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button size="sm" variant="outline" icon="key" wire:click="openResetForm({{ $userId }})">
                        Reset Password
                    </flux:button>

                    @if (! $user->is(auth()->user()) && $user->role === \App\Enums\Role::Admin)
                        <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $userId }})">
                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <x-empty-state icon="users" title="Tidak ada pengguna"
                description="Coba ubah filter atau kata pencarian." />
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->users()->links() }}
    </div>

    {{-- Modal tambah admin --}}
    <flux:modal name="tambah-admin" wire:model="showCreateForm" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Tambah Admin</flux:heading>

            <form wire:submit="createAdmin" class="space-y-4">
                <flux:input wire:model="newName" label="Nama" type="text" required placeholder="Nama lengkap" />

                <flux:input wire:model="newEmail" label="Email" type="email" required placeholder="admin@sinilaundry.test" />

                <flux:input wire:model="newPhone" label="Nomor HP" type="tel" required inputmode="numeric"
                    placeholder="08xxxxxxxxxx" />

                <flux:input wire:model="newPassword" label="Password awal" type="password" required viewable
                    placeholder="Minimal 8 karakter" />

                <div class="flex items-center justify-end gap-3">
                    <flux:button type="button" variant="outline" x-on:click="$flux.modal('tambah-admin').close()">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" data-test="create-admin-button">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Modal reset password --}}
    <flux:modal name="reset-password" wire:model="showResetForm" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Atur Ulang Password</flux:heading>

            <form wire:submit="resetPassword" class="space-y-4">
                <flux:input wire:model="resetPassword" label="Password baru" type="password" required viewable
                    placeholder="Minimal 8 karakter" />

                <div class="flex items-center justify-end gap-3">
                    <flux:button type="button" variant="outline" x-on:click="$flux.modal('reset-password').close()">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" data-test="save-reset-password-button">
                        Simpan
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
