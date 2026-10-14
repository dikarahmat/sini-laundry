<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicStatusCheckTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $service = Service::factory()->perKg()->create(['price' => 6000]);

        $customer = Customer::factory()->walkIn()->create([
            'name' => 'Rina Pelanggan',
            'phone' => '081234567890',
            'address' => 'Jl. Rahasia No. 5',
        ]);

        $this->order = Order::factory()->create([
            'code' => 'SL-20261014-001',
            'customer_id' => $customer->id,
            'source' => OrderSource::WalkIn,
            'status' => OrderStatus::Washing,
            'total_price' => 12000,
        ]);

        $this->order->items()->create([
            'service_id' => $service->id,
            'quantity' => 2.0,
            'unit_price' => 6000,
            'subtotal' => 12000,
        ]);
    }

    public function test_halaman_cek_status_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('cek-status'))->assertOk();
    }

    public function test_kombinasi_benar_menampilkan_status(): void
    {
        $component = Livewire::test('pages::cek-status')
            ->set('code', 'SL-20261014-001')
            ->set('lastFour', '7890')
            ->call('check')
            ->assertHasNoErrors()
            ->assertSee('Dicuci')
            ->assertSee('SL-20261014-001')
            ->assertSee('Cucian kamu sedang dicuci');

        // Nama tampil sebagai nama depan saja.
        $component->assertSee('Halo, Rina!');
    }

    public function test_kombinasi_salah_menampilkan_pesan_generik(): void
    {
        Livewire::test('pages::cek-status')
            ->set('code', 'SL-20261014-999')
            ->set('lastFour', '7890')
            ->call('check')
            ->assertHasErrors(['code']);

        Livewire::test('pages::cek-status')
            ->set('code', 'SL-20261014-001')
            ->set('lastFour', '0000')
            ->call('check')
            ->assertHasErrors(['code']);
    }

    public function test_data_sensitif_tidak_ditampilkan(): void
    {
        $component = Livewire::test('pages::cek-status')
            ->set('code', 'SL-20261014-001')
            ->set('lastFour', '7890')
            ->call('check');

        $rendered = $component->html();

        // Nomor HP penuh, alamat, dan nama lengkap tidak boleh muncul.
        $this->assertStringNotContainsString('081234567890', $rendered);
        $this->assertStringNotContainsString('Jl. Rahasia', $rendered);
        $this->assertStringNotContainsString('Rina Pelanggan', $rendered);
    }

    public function test_percobaan_keenam_diblokir(): void
    {
        RateLimiter::clear(request()->ip());

        for ($i = 0; $i < 5; $i++) {
            Livewire::test('pages::cek-status')
                ->set('code', 'SL-20261014-000')
                ->set('lastFour', '0000')
                ->call('check');
        }

        $component = Livewire::test('pages::cek-status')
            ->set('code', 'SL-20261014-001')
            ->set('lastFour', '7890')
            ->call('check');

        $component->assertHasErrors(['code']);
        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            $component->errors()->get('code')[0] ?? '',
        );
    }

    public function test_kode_dari_url_terisi_otomatis(): void
    {
        Livewire::withQueryParams(['kode' => 'SL-20261014-001'])
            ->test('pages::cek-status')
            ->assertSet('code', 'SL-20261014-001');
    }
}
