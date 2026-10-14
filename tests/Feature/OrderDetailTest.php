<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->role(Role::Admin)->create();
        $this->order = Order::factory()->create([
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Unpaid,
            'total_price' => 20000,
            'total_weight' => 2.0,
        ]);
    }

    /**
     * Alur status lengkap dari received sampai picked_up dengan pembayaran lunas.
     */
    public function test_alur_status_lengkap_sampai_diambil(): void
    {
        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order]);

        $component->call('advance'); // received → washing
        $this->assertSame(OrderStatus::Washing, $this->order->refresh()->status);

        $component->call('advance'); // washing → ironing
        $this->assertSame(OrderStatus::Ironing, $this->order->refresh()->status);

        $component->call('advance'); // ironing → done
        $this->assertSame(OrderStatus::Done, $this->order->refresh()->status);

        // Belum lunas: tombol tandai Diambil ditolak.
        $component->call('advance');
        $this->assertSame(OrderStatus::Done, $this->order->refresh()->status);

        // Catat pembayaran lunas, lalu tandai Diambil.
        $component->set('paymentAmount', 20000)
            ->set('paymentMethod', 'cash')
            ->call('recordPayment');

        $this->assertSame(PaymentStatus::Paid, $this->order->refresh()->payment_status);

        $component->call('advance');

        $this->assertSame(OrderStatus::PickedUp, $this->order->refresh()->status);
        $this->assertNotNull($this->order->refresh()->picked_up_at);

        // Semua perubahan tercatat di log (terbaru dulu, jadi dibalik untuk urutan waktu).
        $statuses = $this->order->statusLogs()->pluck('status')
            ->map(fn ($status): string => $status->value)
            ->reverse()
            ->values()
            ->all();

        $this->assertSame([
            OrderStatus::Washing->value,
            OrderStatus::Ironing->value,
            OrderStatus::Done->value,
            OrderStatus::PickedUp->value,
        ], $statuses);
    }

    public function test_pembayaran_sebagian_lalu_lunas(): void
    {
        $component = Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order]);

        $component->set('paymentAmount', 8000)->call('recordPayment');
        $this->assertSame(PaymentStatus::Partial, $this->order->refresh()->payment_status);
        $this->assertSame(12000, $this->order->remainingAmount());

        $component->set('paymentAmount', 12000)->call('recordPayment');
        $this->assertSame(PaymentStatus::Paid, $this->order->refresh()->payment_status);
        $this->assertSame(0, $this->order->remainingAmount());
    }

    public function test_validasi_jumlah_pembayaran(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('paymentAmount', 0)
            ->call('recordPayment')
            ->assertHasErrors(['paymentAmount']);
    }

    public function test_admin_tidak_bisa_mundurkan_status(): void
    {
        $this->order->update(['status' => OrderStatus::Washing]);

        // Policy: admin tidak punya izin mundurkan status.
        $this->assertFalse(Gate::forUser($this->staff)->allows('rollbackStatus', $this->order));

        // Owner punya izin.
        $owner = User::factory()->role(Role::Owner)->create();
        $this->assertTrue(Gate::forUser($owner)->allows('rollbackStatus', $this->order));

        // Aksi mundurkan status oleh admin tidak mengubah status pesanan.
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('rollbackNote', 'Salah input')
            ->call('rollback');

        $this->assertSame(OrderStatus::Washing, $this->order->refresh()->status);
    }

    public function test_owner_bisa_mundurkan_status_dengan_catatan(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();
        $this->order->update(['status' => OrderStatus::Washing]);

        Livewire::actingAs($owner)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->call('openRollbackForm')
            ->set('rollbackNote', 'Salah input berat')
            ->call('rollback')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Received, $this->order->refresh()->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $this->order->id,
            'status' => OrderStatus::Received->value,
            'note' => 'Salah input berat',
        ]);
    }

    public function test_mundur_tanpa_catatan_ditolak(): void
    {
        $owner = User::factory()->role(Role::Owner)->create();
        $this->order->update(['status' => OrderStatus::Washing]);

        Livewire::actingAs($owner)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->call('openRollbackForm')
            ->set('rollbackNote', '')
            ->call('rollback')
            ->assertHasErrors(['rollbackNote']);
    }

    public function test_pembatalan_sebelum_dicuci(): void
    {
        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('showCancelDialog', true)
            ->set('cancelNote', 'Pelanggan batal')
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $this->order->refresh()->status);
    }

    public function test_pembatalan_setelah_dicuci_ditolak(): void
    {
        $this->order->update(['status' => OrderStatus::Washing]);

        Livewire::actingAs($this->staff)
            ->test('pages::admin.pesanan.detail', ['order' => $this->order])
            ->set('showCancelDialog', true)
            ->call('cancel');

        $this->assertSame(OrderStatus::Washing, $this->order->refresh()->status);
    }

    public function test_pelanggan_tidak_bisa_membuka_detail_pesanan_orang_lain(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('admin.pesanan.show', $this->order))->assertForbidden();
    }
}
