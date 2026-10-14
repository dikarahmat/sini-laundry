<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\ServiceUnit;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerSideTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'Cuci + Setrika',
            'unit' => ServiceUnit::Kilogram,
            'price' => 6000,
            'duration_hours' => 48,
            'is_express' => false,
            'is_active' => true,
        ]);

        $this->customer = User::factory()->role(Role::Customer)->create([
            'phone' => '081234567890',
        ]);
    }

    private function orderFor(User $user, array $attributes = []): Order
    {
        $customer = Customer::ensureForUser($user);

        return Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'source' => OrderSource::Online,
            'status' => OrderStatus::PendingConfirmation,
            'payment_status' => PaymentStatus::Unpaid,
            'total_price' => 6000,
        ], $attributes));
    }

    public function test_beranda_pelanggan_menampilkan_pesanan_aktif(): void
    {
        $order = $this->orderFor($this->customer);

        $this->actingAs($this->customer)
            ->get(route('pesanan.index'))
            ->assertOk()
            ->assertSee('Pesanan Aktif')
            ->assertSee($order->code);
    }

    public function test_pelanggan_hanya_melihat_pesanannya_sendiri(): void
    {
        $mine = $this->orderFor($this->customer);

        $other = User::factory()->role(Role::Customer)->create();
        $others = $this->orderFor($other);

        $this->actingAs($this->customer)->get(route('pesanan.show', $mine))->assertOk();
        $this->actingAs($this->customer)->get(route('pesanan.show', $others))->assertForbidden();

        $this->actingAs($this->customer)
            ->get(route('pesanan.index'))
            ->assertSee($mine->code)
            ->assertDontSee($others->code);
    }

    public function test_pesanan_online_dibuat_dengan_status_menunggu_konfirmasi(): void
    {
        Livewire::actingAs($this->customer)
            ->test('pages::pesanan.baru')
            ->set('rows.0.service_id', $this->service->id)
            ->set('rows.0.quantity', 2.4)
            ->set('pickupDate', now()->addDays(2)->format('Y-m-d'))
            ->set('notes', 'Pisahkan putih')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(OrderSource::Online, $order->source);
        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);
        $this->assertSame(15000, $order->total_price); // 2.4 → 2.5 kg x 6000
        $this->assertSame(2.5, (float) $order->total_weight);
        $this->assertSame('Pisahkan putih', $order->notes);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => OrderStatus::PendingConfirmation->value,
            'changed_by' => null,
        ]);
    }

    public function test_validasi_pesanan_online(): void
    {
        Livewire::actingAs($this->customer)
            ->test('pages::pesanan.baru')
            ->set('rows.0.service_id', '')
            ->set('rows.0.quantity', '')
            ->set('pickupDate', now()->subDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['rows.0.service_id', 'rows.0.quantity', 'pickupDate']);

        $this->assertSame(0, Order::count());
    }

    public function test_pelanggan_bisa_membatalkan_saat_menunggu_konfirmasi(): void
    {
        $order = $this->orderFor($this->customer);

        Livewire::actingAs($this->customer)
            ->test('pages::pesanan.detail', ['order' => $order])
            ->set('showCancelDialog', true)
            ->set('cancelNote', 'Berubah rencana')
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_pelanggan_tidak_bisa_membatalkan_setelah_dikonfirmasi(): void
    {
        $order = $this->orderFor($this->customer, ['status' => OrderStatus::Washing]);

        Livewire::actingAs($this->customer)
            ->test('pages::pesanan.detail', ['order' => $order])
            ->set('showCancelDialog', true)
            ->call('cancel');

        $this->assertSame(OrderStatus::Washing, $order->refresh()->status);
    }

    public function test_admin_tidak_bisa_membuka_halaman_pesanan_pelanggan(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('pesanan.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pesanan.baru'))->assertForbidden();
    }
}
