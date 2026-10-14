<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderListTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->role(Role::Admin)->create();
    }

    private function order(string $code, OrderStatus $status, string $customerName, ?string $date = null): Order
    {
        $customer = Customer::factory()->walkIn()->create(['name' => $customerName, 'phone' => '0812'.random_int(1000000, 9999999)]);

        $order = Order::factory()->create([
            'code' => $code,
            'customer_id' => $customer->id,
            'status' => $status,
            'created_at' => $date ? now()->parse($date) : now(),
        ]);

        return $order;
    }

    public function test_daftar_pesanan_menampilkan_semua_secara_default(): void
    {
        $this->order('SL-20261014-001', OrderStatus::Received, 'Andi');
        $this->order('SL-20261014-002', OrderStatus::Done, 'Siti');

        $component = Livewire::actingAs($this->staff)->test('pages::admin.pesanan');

        $this->assertSame(2, $component->instance()->orders()->total());
    }

    public function test_filter_status(): void
    {
        $this->order('SL-20261014-001', OrderStatus::Received, 'Andi');
        $this->order('SL-20261014-002', OrderStatus::Done, 'Siti');
        $this->order('SL-20261014-003', OrderStatus::Done, 'Rina');

        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan')
            ->set('status', 'done');

        $this->assertSame(2, $component->instance()->orders()->total());
        $this->assertSame(2, $component->instance()->statusCounts()['done'] ?? 0);
    }

    public function test_filter_status_bayar(): void
    {
        $orderA = $this->order('SL-20261014-001', OrderStatus::Received, 'Andi');
        $this->order('SL-20261014-002', OrderStatus::Received, 'Siti');

        $orderA->update(['payment_status' => PaymentStatus::Paid]);

        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan')
            ->set('paymentStatus', 'paid');

        $this->assertSame(1, $component->instance()->orders()->total());
    }

    public function test_pencarian_berdasarkan_kode_pesanan(): void
    {
        $this->order('SL-20261014-001', OrderStatus::Received, 'Andi');
        $this->order('SL-20261014-002', OrderStatus::Received, 'Siti');

        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan')
            ->set('search', 'SL-20261014-002');

        $this->assertSame(1, $component->instance()->orders()->total());
    }

    public function test_pencarian_berdasarkan_nama_pelanggan(): void
    {
        $this->order('SL-20261014-001', OrderStatus::Received, 'Andi Wijaya');
        $this->order('SL-20261014-002', OrderStatus::Received, 'Siti Rahayu');

        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan')
            ->set('search', 'Rahayu');

        $this->assertSame(1, $component->instance()->orders()->total());
    }

    public function test_filter_rentang_tanggal(): void
    {
        $this->order('SL-20260901-001', OrderStatus::Received, 'Andi', '3 days ago');
        $this->order('SL-20261014-002', OrderStatus::Received, 'Siti', 'today');

        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan')
            ->set('dateFrom', now()->toDateString())
            ->set('dateTo', now()->toDateString());

        $this->assertSame(1, $component->instance()->orders()->total());
    }

    public function test_halaman_terbuka_untuk_admin_dan_owner(): void
    {
        $this->actingAs($this->staff)->get(route('admin.pesanan.index'))->assertOk();

        $owner = User::factory()->role(Role::Owner)->create();
        $this->actingAs($owner)->get(route('admin.pesanan.index'))->assertOk();
    }

    public function test_pelanggan_mendapat_403(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('admin.pesanan.index'))->assertForbidden();
    }

    public function test_detail_pesanan_hanya_untuk_admin_dan_owner(): void
    {
        $order = $this->order('SL-20261014-001', OrderStatus::Received, 'Andi');

        $this->actingAs($this->staff)->get(route('admin.pesanan.show', $order))->assertOk();

        $customer = User::factory()->role(Role::Customer)->create();
        $this->actingAs($customer)->get(route('admin.pesanan.show', $order))->assertForbidden();
    }
}
