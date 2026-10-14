<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
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

class NewOrderFormTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $staff;

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

        $this->staff = User::factory()->role(Role::Admin)->create();
    }

    public function test_pesanan_walk_in_berhasil_dibuat_dengan_total_benar(): void
    {
        $customer = Customer::factory()->walkIn()->create();

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('selectedCustomerId', $customer->id)
            ->set('rows.0.service_id', $this->service->id)
            ->set('rows.0.quantity', 2.4)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.pesanan.index', absolute: false));

        $order = Order::firstOrFail();

        $this->assertSame(OrderSource::WalkIn, $order->source);
        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame($this->staff->id, $order->created_by);
        $this->assertSame(15000, $order->total_price); // 2.4 dibulatkan ke 2.5 x 6000
        $this->assertSame(2.5, (float) $order->total_weight);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'service_id' => $this->service->id,
            'quantity' => 2.5,
            'unit_price' => 6000,
            'subtotal' => 15000,
        ]);

        // Log status awal tercatat.
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => OrderStatus::Received->value,
            'changed_by' => $this->staff->id,
        ]);
    }

    public function test_pembayaran_awal_tercatat(): void
    {
        $customer = Customer::factory()->walkIn()->create();

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('selectedCustomerId', $customer->id)
            ->set('rows.0.service_id', $this->service->id)
            ->set('rows.0.quantity', 2)
            ->set('paymentAmount', 6000)
            ->set('paymentMethod', 'transfer')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(PaymentStatus::Partial, $order->refresh()->payment_status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 6000,
            'method' => PaymentMethod::Transfer->value,
            'received_by' => $this->staff->id,
        ]);
    }

    public function test_validasi_gagal_bila_tanpa_item(): void
    {
        $customer = Customer::factory()->walkIn()->create();

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('selectedCustomerId', $customer->id)
            ->set('rows.0.service_id', '')
            ->set('rows.0.quantity', '')
            ->call('save')
            ->assertHasErrors(['rows.0.service_id', 'rows.0.quantity']);

        $this->assertSame(0, Order::count());
    }

    public function test_validasi_gagal_bila_pelanggan_belum_dipilih(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('rows.0.service_id', $this->service->id)
            ->set('rows.0.quantity', 1)
            ->call('save')
            ->assertHasErrors(['selectedCustomerId']);

        $this->assertSame(0, Order::count());
    }

    public function test_pelanggan_baru_bisa_dibuat_tanpa_pindah_halaman(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('showNewCustomerForm', true)
            ->set('newCustomerName', 'Pelanggan Baru')
            ->set('newCustomerPhone', '081200000009')
            ->call('createWalkInCustomer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'Pelanggan Baru',
            'phone' => '081200000009',
            'user_id' => null,
        ]);
    }

    public function test_harga_ekspres_ditambahkan(): void
    {
        $express = Service::create([
            'name' => 'Express Cuci + Setrika',
            'unit' => ServiceUnit::Kilogram,
            'price' => 8000,
            'duration_hours' => 6,
            'is_express' => true,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->walkIn()->create();

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan-baru')
            ->set('selectedCustomerId', $customer->id)
            ->set('rows.0.service_id', $express->id)
            ->set('rows.0.quantity', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(12000, Order::firstOrFail()->total_price); // 8000 + 50%
    }

    public function test_pelanggan_tidak_bisa_membuka_form_pesanan_baru(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('admin.pesanan.baru'))->assertForbidden();
    }

    public function test_owner_bisa_membuka_form_pesanan_baru(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($owner)->get(route('admin.pesanan.baru'))->assertOk();
    }
}
