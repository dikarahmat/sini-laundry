<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalisasi_nomor_hp_ke_format_08(): void
    {
        $this->assertSame('081234567890', Phone::normalize('081234567890'));
        $this->assertSame('081234567890', Phone::normalize('+6281234567890'));
        $this->assertSame('081234567890', Phone::normalize('6281234567890'));
        $this->assertSame('081234567890', Phone::normalize('0812-3456-7890'));
        $this->assertTrue(Phone::isValid('+6281234567890'));
        $this->assertFalse(Phone::isValid('12345'));
        $this->assertSame('7890', Phone::lastFourDigits('+6281234567890'));
    }

    public function test_registrasi_membuat_baris_customer(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Rina Pelanggan',
            'email' => 'rina@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        $customer = Customer::firstWhere('phone', '081234567890');
        $this->assertNotNull($customer);
        $this->assertSame('Rina Pelanggan', $customer->name);
        $this->assertNotNull($customer->user_id);
    }

    public function test_login_pertama_menautkan_akun_ke_pelanggan_walk_in(): void
    {
        $walkIn = Customer::factory()->walkIn()->create([
            'name' => 'Rina (walk-in)',
            'phone' => '081234567890',
        ]);

        $user = User::factory()->create([
            'phone' => '081234567890',
            'role' => Role::Customer,
        ]);

        Event::dispatch(new \Illuminate\Auth\Events\Login('web', $user, false));

        $this->assertSame($walkIn->id, $user->refresh()->customer->id);
        $this->assertSame($user->id, $walkIn->refresh()->user_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_admin_bisa_menambah_pelanggan_walk_in(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.pelanggan')
            ->call('create')
            ->set('name', 'Pak Budi')
            ->set('phone', '081999888777')
            ->set('address', 'Jl. Kenanga 4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'Pak Budi',
            'phone' => '081999888777',
            'address' => 'Jl. Kenanga 4',
            'user_id' => null,
        ]);
    }

    public function test_admin_bisa_mengubah_data_pelanggan(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::factory()->walkIn()->create(['phone' => '081234567890']);

        Livewire::actingAs($admin)
            ->test('pages::admin.pelanggan')
            ->call('edit', $customer->id)
            ->set('name', 'Nama Baru')
            ->set('phone', '+6281234567890')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru', $customer->refresh()->name);
        $this->assertSame('081234567890', $customer->phone);
    }

    public function test_pencarian_pelanggan_berdasarkan_nama_dan_hp(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        Customer::factory()->walkIn()->create(['name' => 'Andi Wijaya', 'phone' => '081200000001']);
        Customer::factory()->walkIn()->create(['name' => 'Siti Rahayu', 'phone' => '081200000002']);

        $byName = Livewire::actingAs($admin)
            ->test('pages::admin.pelanggan')
            ->set('search', 'Andi');
        $this->assertSame(1, $byName->instance()->customers->total());

        $byPhone = Livewire::actingAs($admin)
            ->test('pages::admin.pelanggan')
            ->set('search', '0002');
        $this->assertSame(1, $byPhone->instance()->customers->total());
    }

    public function test_pelanggan_tidak_bisa_membuka_halaman_admin_pelanggan(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('admin.pelanggan.index'))->assertForbidden();
    }

    public function test_owner_bisa_membuka_halaman_admin_pelanggan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($owner)->get(route('admin.pelanggan.index'))->assertOk();
    }
}
