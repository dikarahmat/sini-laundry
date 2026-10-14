<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Otorisasi setiap route di architecture.md bagian 7:
 * peran yang boleh dan yang harus 403/redirect.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_publik_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('cek-status'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_route_pesanan_hanya_untuk_pelanggan(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($customer)->get(route('pesanan.index'))->assertOk();
        $this->actingAs($admin)->get(route('pesanan.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('pesanan.index'))->assertForbidden();

        $this->actingAs($customer)->get(route('pesanan.baru'))->assertOk();
        $this->actingAs($admin)->get(route('pesanan.baru'))->assertForbidden();
    }

    public function test_route_admin_hanya_untuk_admin_dan_owner(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($admin)->get(route('admin.pesanan.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.pesanan.index'))->assertOk();
        $this->actingAs($customer)->get(route('admin.pesanan.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.pesanan.baru'))->assertOk();
        $this->actingAs($owner)->get(route('admin.pesanan.baru'))->assertOk();
        $this->actingAs($customer)->get(route('admin.pesanan.baru'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.pelanggan.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.pelanggan.index'))->assertOk();
        $this->actingAs($customer)->get(route('admin.pelanggan.index'))->assertForbidden();
    }

    public function test_detail_pesanan_admin_hanya_untuk_admin_dan_owner(): void
    {
        $order = Order::factory()->create();

        $customer = User::factory()->role(Role::Customer)->create();
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('admin.pesanan.show', $order))->assertOk();
        $this->actingAs($customer)->get(route('admin.pesanan.show', $order))->assertForbidden();
    }

    public function test_nota_hanya_untuk_admin_dan_owner(): void
    {
        $order = Order::factory()->create();

        $customer = User::factory()->role(Role::Customer)->create();
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($owner)->get(route('admin.pesanan.nota', $order))->assertOk();
        $this->actingAs($customer)->get(route('admin.pesanan.nota', $order))->assertForbidden();
    }

    public function test_route_owner_hanya_untuk_owner(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $owner = User::factory()->role(Role::Owner)->create();

        foreach ([
            'owner.dashboard',
            'owner.laporan',
            'owner.layanan',
            'owner.pengguna',
        ] as $route) {
            $this->actingAs($owner)->get(route($route))->assertOk();
            $this->actingAs($admin)->get(route($route))->assertForbidden();
            $this->actingAs($customer)->get(route($route))->assertForbidden();
        }
    }

    public function test_detail_pesanan_pelanggan_hanya_milik_sendiri(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();
        $other = User::factory()->role(Role::Customer)->create();

        $mine = Order::factory()->create(['customer_id' => Customer::ensureForUser($customer)->id]);
        $theirs = Order::factory()->create(['customer_id' => Customer::ensureForUser($other)->id]);

        $this->actingAs($customer)->get(route('pesanan.show', $mine))->assertOk();
        $this->actingAs($customer)->get(route('pesanan.show', $theirs))->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login_untuk_route_terproteksi(): void
    {
        $this->get(route('pesanan.index'))->assertRedirect(route('login'));
        $this->get(route('admin.pesanan.index'))->assertRedirect(route('login'));
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
    }
}
