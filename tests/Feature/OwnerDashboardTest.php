<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->role(Role::Owner)->create();
    }

    public function test_angka_dashboard_sesuai_data(): void
    {
        // Pembayaran hari ini: 2 x 25.000 + DP 20.000 = 70.000.
        $orderA = Order::factory()->create([
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Partial,
            'total_price' => 50000,
        ]);
        $orderB = Order::factory()->create([
            'status' => OrderStatus::Washing,
            'payment_status' => PaymentStatus::Partial,
            'total_price' => 30000,
        ]);

        Payment::factory()->create(['order_id' => $orderA->id, 'amount' => 25000, 'paid_at' => now()]);
        Payment::factory()->create(['order_id' => $orderB->id, 'amount' => 25000, 'paid_at' => now()]);

        // Pesanan aktif: received + washing + received(DP) = 3.
        // Selesai belum diambil: 1 (lunas).
        Order::factory()->paid()->create(['status' => OrderStatus::Done, 'total_price' => 40000]);

        // Belum lunas: sisa 25.000 + 5.000 + 40.000 = 70.000.
        $orderC = Order::factory()->create([
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Partial,
            'total_price' => 60000,
        ]);
        Payment::factory()->create(['order_id' => $orderC->id, 'amount' => 20000]);

        $component = Livewire::actingAs($this->owner)->test('pages::owner.dashboard');

        // 25.000 + 25.000 + 20.000 (DP) diterima hari ini = 70.000.
        $this->assertSame(70000, $component->instance()->todayRevenue());
        $this->assertSame(3, $component->instance()->activeOrders());
        $this->assertSame(1, $component->instance()->doneNotPickedUp());
        $this->assertSame(70000, $component->instance()->unpaidTotal());
    }

    public function test_grafik_7_hari_memiliki_tujuh_titik(): void
    {
        $component = Livewire::actingAs($this->owner)->test('pages::owner.dashboard');

        $this->assertCount(7, $component->instance()->dailyRevenue());
    }

    public function test_hanya_owner_yang_bisa_membuka_dashboard(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($admin)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('owner.dashboard'))->assertForbidden();

        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk();
    }
}
