<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\ServiceUnit;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alur end-to-end: pelanggan memesan online, admin mengonfirmasi dan menimbang,
 * status berjalan sampai picked_up dengan pembayaran lunas,
 * owner melihat pendapatan di laporan.
 */
class EndToEndTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

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
    }

    public function test_alur_lengkap_dari_pesan_online_sampai_lunas(): void
    {
        // 1. Pelanggan mendaftar dan memesan online.
        $this->post(route('register.store'), [
            'name' => 'Rina Pelanggan',
            'email' => 'rina@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('pesanan.index'));

        Livewire::test('pages::pesanan.baru')
            ->set('rows.0.service_id', $this->service->id)
            ->set('rows.0.quantity', 2.4)
            ->set('pickupDate', now()->addDays(2)->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);
        $this->assertSame(15000, $order->total_price); // estimasi 2.4 → 2.5 kg x 6000

        $this->post(route('logout'));

        // 2. Admin mengonfirmasi dengan berat aktual.
        $admin = User::factory()->role(Role::Admin)->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.pesanan.detail', ['order' => $order])
            ->set('confirmingItems.'.$order->items->first()->id.'.quantity', 3.2)
            ->call('confirmOrder')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertSame(21000, $order->total_price); // 3.2 → 3.5 kg x 6000

        $this->post(route('logout'));

        // 3. Status berjalan sampai Selesai.
        $staff = User::factory()->role(Role::Admin)->create();

        Livewire::actingAs($staff)
            ->test('pages::admin.pesanan.detail', ['order' => $order])
            ->call('advance')
            ->call('advance')
            ->call('advance');

        $this->assertSame(OrderStatus::Done, $order->refresh()->status);

        // 4. Pembayaran DP lalu lunas.
        Livewire::actingAs($staff)
            ->test('pages::admin.pesanan.detail', ['order' => $order])
            ->set('paymentAmount', 10000)
            ->set('paymentMethod', 'cash')
            ->call('recordPayment');

        $this->assertSame(PaymentStatus::Partial, $order->refresh()->payment_status);

        Livewire::actingAs($staff)
            ->test('pages::admin.pesanan.detail', ['order' => $order])
            ->set('paymentAmount', $order->remainingAmount())
            ->set('paymentMethod', 'transfer')
            ->call('recordPayment');

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
        $this->assertSame(0, $order->remainingAmount());

        // 5. Tandai Diambil.
        Livewire::actingAs($staff)
            ->test('pages::admin.pesanan.detail', ['order' => $order])
            ->call('advance');

        $this->assertSame(OrderStatus::PickedUp, $order->refresh()->status);

        // 6. Owner melihat pendapatan hari ini di laporan.
        $owner = User::factory()->role(Role::Owner)->create();

        $component = Livewire::actingAs($owner)
            ->test('pages::owner.laporan')
            ->set('dateFrom', now()->toDateString())
            ->set('dateTo', now()->toDateString());

        $this->assertSame(21000, $component->instance()->totalRevenue());
    }
}
