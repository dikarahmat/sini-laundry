<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\ServiceUnit;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->role(Role::Owner)->create();
    }

    public function test_angka_laporan_sesuai_rentang_tanggal(): void
    {
        $today = Carbon::today();

        // Pesanan dan pembayaran di dalam rentang.
        $orderA = Order::factory()->create([
            'created_at' => $today->copy()->setTime(10, 0),
            'total_price' => 60000,
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $orderB = Order::factory()->create([
            'created_at' => $today->copy()->setTime(14, 0),
            'total_price' => 40000,
            'status' => OrderStatus::Washing,
            'payment_status' => PaymentStatus::Paid,
        ]);

        Payment::factory()->create(['order_id' => $orderA->id, 'amount' => 60000, 'paid_at' => $today->copy()->setTime(10, 5)]);
        Payment::factory()->transfer()->create(['order_id' => $orderB->id, 'amount' => 40000, 'paid_at' => $today->copy()->setTime(14, 30)]);

        $component = Livewire::actingAs($this->owner)
            ->test('pages::owner.laporan')
            ->set('dateFrom', $today->toDateString())
            ->set('dateTo', $today->toDateString());

        $this->assertSame(100000, $component->instance()->totalRevenue());
        $this->assertSame(2, $component->instance()->orderCount());
        $this->assertSame(50000, $component->instance()->averageOrderValue());
        $this->assertSame(0, $component->instance()->unpaidTotal());
        $this->assertCount(1, $component->instance()->dailyRows());
        $this->assertSame(2, $component->instance()->paymentMethodRows()->count());
    }

    public function test_belum_lunas_masuk_laporan(): void
    {
        $today = Carbon::today();

        $order = Order::factory()->create([
            'created_at' => $today,
            'total_price' => 50000,
            'payment_status' => PaymentStatus::Partial,
        ]);

        Payment::factory()->create(['order_id' => $order->id, 'amount' => 20000, 'paid_at' => $today]);

        $component = Livewire::actingAs($this->owner)
            ->test('pages::owner.laporan')
            ->set('dateFrom', $today->toDateString())
            ->set('dateTo', $today->toDateString());

        $this->assertSame(30000, $component->instance()->unpaidTotal());
    }

    public function test_layanan_terlaris_diagregasi(): void
    {
        $today = Carbon::today();

        $service = Service::create([
            'name' => 'Cuci + Setrika',
            'unit' => ServiceUnit::Kilogram,
            'price' => 8000,
            'duration_hours' => 48,
            'is_express' => false,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'created_at' => $today,
            'total_price' => 40000,
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $order->items()->create([
            'service_id' => $service->id,
            'quantity' => 5.0,
            'unit_price' => 8000,
            'subtotal' => 40000,
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test('pages::owner.laporan')
            ->set('dateFrom', $today->toDateString())
            ->set('dateTo', $today->toDateString());

        $rows = $component->instance()->serviceRows();

        $this->assertCount(1, $rows);
        $this->assertSame('Cuci + Setrika', $rows->first()->name);
        $this->assertSame(5.0, (float) $rows->first()->total_quantity);
        $this->assertSame(40000, (int) $rows->first()->revenue);
    }

    public function test_pintasan_bulan_ini(): void
    {
        $component = Livewire::actingAs($this->owner)->test('pages::owner.laporan');

        $this->assertSame(Carbon::now()->startOfMonth()->toDateString(), $component->get('dateFrom'));

        $component->call('shortcut', 'last7');

        $this->assertSame(Carbon::today()->subDays(6)->toDateString(), $component->get('dateFrom'));
        $this->assertSame(Carbon::today()->toDateString(), $component->get('dateTo'));
    }

    public function test_hanya_owner_yang_bisa_membuka_laporan(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('owner.laporan'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('owner.laporan'))->assertOk();
    }
}
