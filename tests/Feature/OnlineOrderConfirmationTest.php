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

class OnlineOrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $staff;

    private Order $order;

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

        $customer = Customer::factory()->walkIn()->create();

        $this->order = Order::factory()->online()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingConfirmation,
            'payment_status' => PaymentStatus::Unpaid,
            'total_price' => 6000,
            'total_weight' => 1.0,
        ]);

        $this->order->items()->create([
            'service_id' => $this->service->id,
            'quantity' => 1.0, // estimasi pelanggan
            'unit_price' => 6000,
            'subtotal' => 6000,
        ]);
    }

    public function test_konfirmasi_menghitung_ulang_total_berdasarkan_berat_aktual(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('confirmingItems.'.$this->order->items->first()->id.'.quantity', 3.2)
            ->call('confirmOrder')
            ->assertHasNoErrors();

        $this->order->refresh();

        // 3.2 kg dibulatkan ke 3.5 kg x 6000 = 21000.
        $this->assertSame(21000, $this->order->total_price);
        $this->assertSame(3.5, (float) $this->order->total_weight);
        $this->assertSame(OrderStatus::Received, $this->order->status);
        $this->assertNotNull($this->order->estimated_done_at);

        // Item diperbarui.
        $item = $this->order->items()->first();
        $this->assertSame(3.5, (float) $item->quantity);
        $this->assertSame(21000, $item->subtotal);

        // Log status tercatat.
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $this->order->id,
            'status' => OrderStatus::Received->value,
            'changed_by' => $this->staff->id,
        ]);
    }

    public function test_konfirmasi_bisa_mengganti_layanan(): void
    {
        $lain = Service::create([
            'name' => 'Setrika Saja',
            'unit' => ServiceUnit::Kilogram,
            'price' => 5000,
            'duration_hours' => 24,
            'is_express' => false,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('confirmingItems.'.$this->order->items->first()->id.'.service_id', $lain->id)
            ->set('confirmingItems.'.$this->order->items->first()->id.'.quantity', 2)
            ->call('confirmOrder')
            ->assertHasNoErrors();

        $this->order->refresh();

        $this->assertSame(10000, $this->order->total_price); // 2 kg x 5000
        $this->assertSame($lain->id, $this->order->items()->first()->service_id);
        $this->assertSame(5000, $this->order->items()->first()->unit_price);
    }

    public function test_validasi_konfirmasi(): void
    {
        $itemId = $this->order->items->first()->id;

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('confirmingItems.'.$itemId.'.quantity', 0)
            ->call('confirmOrder')
            ->assertHasErrors(['confirmingItems.'.$itemId.'.quantity']);

        $this->assertSame(OrderStatus::PendingConfirmation, $this->order->refresh()->status);
    }

    public function test_tolak_dengan_alasan_wajib(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('showRejectDialog', true)
            ->set('rejectReason', '')
            ->call('rejectOrder')
            ->assertHasErrors(['rejectReason']);

        $this->assertSame(OrderStatus::PendingConfirmation, $this->order->refresh()->status);
    }

    public function test_tolak_mencatat_alasan(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('showRejectDialog', true)
            ->set('rejectReason', 'Layanan sedang penuh')
            ->call('rejectOrder')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $this->order->refresh()->status);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $this->order->id,
            'status' => OrderStatus::Cancelled->value,
            'note' => 'Layanan sedang penuh',
        ]);
    }

    public function test_tab_menunggu_konfirmasi_memiliki_lencana(): void
    {
        $component = Livewire::actingAs($this->staff)->test('pages::admin.pesanan');

        $this->assertSame(1, $component->instance()->statusCounts()['pending_confirmation'] ?? 0);
    }
}
