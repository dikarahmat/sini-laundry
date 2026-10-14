<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->role(Role::Owner)->create();
    }

    public function test_owner_bisa_membuat_akun_admin(): void
    {
        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->set('showCreateForm', true)
            ->set('newName', 'Admin Baru')
            ->set('newEmail', 'admin.baru@sinilaundry.test')
            ->set('newPhone', '081234567890')
            ->set('newPassword', 'rahasia123')
            ->call('createAdmin')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru@sinilaundry.test',
            'role' => Role::Admin->value,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->assertTrue(Hash::check('rahasia123', User::firstWhere('email', 'admin.baru@sinilaundry.test')->password));
    }

    public function test_email_ganda_ditolak(): void
    {
        User::factory()->create(['email' => 'admin.baru@sinilaundry.test']);

        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->set('newName', 'Admin Baru')
            ->set('newEmail', 'admin.baru@sinilaundry.test')
            ->set('newPhone', '081234567890')
            ->set('newPassword', 'rahasia123')
            ->call('createAdmin')
            ->assertHasErrors(['newEmail']);
    }

    public function test_owner_bisa_reset_password_admin(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->call('openResetForm', $admin->id)
            ->set('resetPassword', 'password-baru-123')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $admin->refresh()->password));
    }

    public function test_owner_bisa_nonaktifkan_dan_aktifkan_admin(): void
    {
        $admin = User::factory()->role(Role::Admin)->create(['is_active' => true]);

        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->call('toggleActive', $admin->id);

        $this->assertFalse($admin->refresh()->is_active);

        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->call('toggleActive', $admin->id);

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $admin = User::factory()->role(Role::Admin)->create([
            'email' => 'nonaktif@sinilaundry.test',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'nonaktif@sinilaundry.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'dinonaktifkan',
            session('errors')->get('email')[0],
        );

        $this->assertGuest();
    }

    public function test_akun_aktif_masih_bisa_login(): void
    {
        $admin = User::factory()->role(Role::Admin)->create([
            'email' => 'aktif@sinilaundry.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'aktif@sinilaundry.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_owner_tidak_bisa_menonaktifkan_diri_sendiri(): void
    {
        Livewire::actingAs($this->owner)
            ->test('pages::owner.pengguna')
            ->call('toggleActive', $this->owner->id);

        // Aksi ditolak (403) dan status owner tetap aktif.
        $this->assertTrue($this->owner->refresh()->is_active);
    }

    public function test_admin_bisa_login_setelah_callback_kustom(): void
    {
        // Callback Fortify::authenticateUsing tetap membolehkan login normal.
        $admin = User::factory()->role(Role::Admin)->create();

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.pesanan.index', absolute: false));
    }
}
