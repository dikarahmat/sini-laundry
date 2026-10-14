<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('pesanan.index'))->assertRedirect(route('login'));
        $this->get(route('admin.pesanan.index'))->assertRedirect(route('login'));
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
    }

    public function test_pelanggan_tidak_bisa_membuka_halaman_admin_dan_owner(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)
            ->get(route('admin.pesanan.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_tidak_bisa_membuka_halaman_owner(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    public function test_owner_bisa_membuka_halaman_admin_dan_owner(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($owner)->get(route('admin.pesanan.index'))->assertOk();
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
    }

    public function test_admin_bisa_membuka_halaman_daftar_pesanan(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('admin.pesanan.index'))->assertOk();
    }

    public function test_pelanggan_bisa_membuka_halaman_pesanan_sendiri(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('pesanan.index'))->assertOk();
    }

    /**
     * Redirect setelah login mengikuti peran.
     */
    public function test_redirect_login_pelanggan(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect(route('pesanan.index', absolute: false));
    }

    public function test_redirect_login_admin(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.pesanan.index', absolute: false));
    }

    public function test_redirect_login_owner(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        $this->post(route('login.store'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard', absolute: false));
    }
}
