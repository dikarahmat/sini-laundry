<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ServiceUnit;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_bisa_menambah_layanan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        Livewire::actingAs($owner)
            ->test('pages::owner.layanan')
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'Cuci Satuan')
            ->set('unit', 'pcs')
            ->set('price', '15000')
            ->set('duration_hours', '24')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'Cuci Satuan',
            'unit' => 'pcs',
            'price' => 15000,
            'duration_hours' => 24,
            'is_active' => true,
        ]);
    }

    public function test_owner_bisa_mengubah_harga_layanan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();
        $service = Service::factory()->perKg()->create(['price' => 6000]);

        Livewire::actingAs($owner)
            ->test('pages::owner.layanan')
            ->call('edit', $service->id)
            ->assertSet('name', $service->name)
            ->set('price', '7000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7000, $service->refresh()->price);
    }

    public function test_owner_bisa_mengaktifkan_dan_menonaktifkan_layanan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();
        $service = Service::factory()->create(['is_active' => true]);

        Livewire::actingAs($owner)
            ->test('pages::owner.layanan')
            ->call('toggleActive', $service->id);

        $this->assertFalse($service->refresh()->is_active);

        Livewire::actingAs($owner)
            ->test('pages::owner.layanan')
            ->call('toggleActive', $service->id);

        $this->assertTrue($service->refresh()->is_active);
    }

    public function test_validasi_layanan_berbahasa_indonesia(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        Livewire::actingAs($owner)
            ->test('pages::owner.layanan')
            ->set('name', '')
            ->set('price', '0')
            ->set('duration_hours', '0')
            ->call('save')
            ->assertHasErrors(['name', 'price', 'duration_hours']);
    }

    public function test_admin_tidak_bisa_membuka_halaman_layanan(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('owner.layanan'))->assertForbidden();
    }

    public function test_pelanggan_tidak_bisa_membuka_halaman_layanan(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('owner.layanan'))->assertForbidden();
    }

    public function test_owner_bisa_membuka_halaman_layanan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($owner)->get(route('owner.layanan'))->assertOk();
    }

    public function test_seeder_layanan_awal_membuat_enam_layanan(): void
    {
        $this->seed(\Database\Seeders\ServiceSeeder::class);

        $this->assertSame(6, Service::count());
        $this->assertDatabaseHas('services', [
            'name' => 'Express Cuci + Setrika',
            'unit' => ServiceUnit::Kilogram->value,
            'price' => 8000,
            'is_express' => true,
        ]);
    }

    public function test_landing_page_menampilkan_layanan_aktif_saja(): void
    {
        Service::factory()->perKg()->create(['name' => 'Layanan Aktif', 'is_active' => true]);
        Service::factory()->create(['name' => 'Layanan Nonaktif', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Layanan Aktif')
            ->assertDontSee('Layanan Nonaktif');
    }
}
