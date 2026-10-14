<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderCodeGenerator;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class OrderBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatusService $statusService;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusService = new OrderStatusService;
        $this->paymentService = new PaymentService;
    }

    private function order(array $attributes = []): Order
    {
        return Order::factory()->create($attributes);
    }

    public function test_kode_pesanan_tidak_kembar_di_hari_yang_sama(): void
    {
        $generator = new OrderCodeGenerator;

        $first = $generator->generate();
        Order::factory()->create(['code' => $first]);

        $second = $generator->generate();
        Order::factory()->create(['code' => $second]);

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^SL-\d{8}-\d{3}$/', $first);
        $this->assertMatchesRegularExpression('/^SL-\d{8}-\d{3}$/', $second);
        $this->assertSame(1, (int) substr($first, -3));
        $this->assertSame(2, (int) substr($second, -3));
    }

    public function test_relasi_pesanan_lengkap(): void
    {
        $order = Order::factory()->create();

        OrderItem::factory()->create(['order_id' => $order->id]);
        OrderStatusLog::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $order->refresh();

        $this->assertCount(1, $order->items);
        $this->assertCount(1, $order->statusLogs);
        $this->assertCount(1, $order->payments);
        $this->assertSame($order->customer_id, $order->customer->id);
        $this->assertSame($order->created_by, $order->creator->id);
    }

    public function test_status_hanya_boleh_maju_satu_tahap(): void
    {
        $order = $this->order(['status' => OrderStatus::Received]);
        $staff = User::factory()->create();

        $updated = $this->statusService->advance($order, $staff);

        $this->assertSame(OrderStatus::Washing, $updated->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => OrderStatus::Washing->value,
            'changed_by' => $staff->id,
        ]);
    }

    public function test_perpindahan_tidak_urut_ditolak(): void
    {
        // Hanya boleh maju satu tahap sesuai alur.
        $this->assertTrue(OrderStatus::Received->canAdvanceTo(OrderStatus::Washing));
        $this->assertFalse(OrderStatus::Received->canAdvanceTo(OrderStatus::Ironing));
        $this->assertFalse(OrderStatus::Received->canAdvanceTo(OrderStatus::Done));
        $this->assertFalse(OrderStatus::PickedUp->canAdvanceTo(OrderStatus::Done));
        $this->assertTrue(OrderStatus::PickedUp->isFinal());
        $this->assertTrue(OrderStatus::Cancelled->isFinal());
        $this->assertFalse(OrderStatus::Washing->canBeCancelled());
        $this->assertTrue(OrderStatus::Received->canBeCancelled());
    }

    public function test_admin_tidak_bisa_mundurkan_status(): void
    {
        $order = $this->order(['status' => OrderStatus::Washing]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->expectException(AuthorizationException::class);

        $this->statusService->rollback($order, OrderStatus::Received, $admin, 'Salah input');
    }

    public function test_owner_bisa_mundurkan_status_dengan_catatan(): void
    {
        $order = $this->order(['status' => OrderStatus::Washing]);
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();

        $updated = $this->statusService->rollback($order, OrderStatus::Received, $owner, 'Salah input berat');

        $this->assertSame(OrderStatus::Received, $updated->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => OrderStatus::Received->value,
            'note' => 'Salah input berat',
        ]);
    }

    public function test_mundur_tanpa_catatan_ditolak(): void
    {
        $order = $this->order(['status' => OrderStatus::Washing]);
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'owner'])->save();

        $this->expectException(ValidationException::class);

        $this->statusService->rollback($order, OrderStatus::Received, $owner, '   ');
    }

    public function test_pembatalan_hanya_sebelum_dicuci(): void
    {
        $staff = User::factory()->create();
        $order = $this->order(['status' => OrderStatus::Received]);

        $updated = $this->statusService->cancel($order, $staff, 'Pelanggan batal');

        $this->assertSame(OrderStatus::Cancelled, $updated->status);

        $washing = $this->order(['status' => OrderStatus::Washing]);

        $this->expectException(RuntimeException::class);

        $this->statusService->cancel($washing, $staff);
    }

    public function test_pesanan_diambil_dan_lunas_tidak_bisa_diubah(): void
    {
        $staff = User::factory()->create();
        $order = $this->order([
            'status' => OrderStatus::PickedUp,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->expectException(RuntimeException::class);

        $this->statusService->advance($order, $staff);
    }

    public function test_status_pembayaran_lunas_parsial_dan_belum(): void
    {
        $staff = User::factory()->create();
        $order = $this->order(['total_price' => 20000]);

        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);

        $this->paymentService->record($order, 8000, PaymentMethod::Cash, $staff);
        $this->assertSame(PaymentStatus::Partial, $order->refresh()->payment_status);

        $this->paymentService->record($order, 12000, PaymentMethod::Transfer, $staff);
        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);

        $this->assertSame(0, $order->remainingAmount());
    }

    public function test_pembayaran_melebihi_total_tetap_lunas(): void
    {
        $staff = User::factory()->create();
        $order = $this->order(['total_price' => 10000]);

        $this->paymentService->record($order, 15000, PaymentMethod::Qris, $staff);

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    public function test_pembayaran_nol_ditolak(): void
    {
        $staff = User::factory()->create();
        $order = $this->order(['total_price' => 10000]);

        $this->expectException(ValidationException::class);

        $this->paymentService->record($order, 0, PaymentMethod::Cash, $staff);
    }

    public function test_sumber_dan_enum_tersimpan(): void
    {
        $online = $this->order(['source' => OrderSource::Online, 'status' => OrderStatus::PendingConfirmation]);

        $this->assertSame(OrderSource::Online, $online->refresh()->source);
        $this->assertSame(OrderStatus::PendingConfirmation, $online->status);
        $this->assertSame(0.0, (float) $this->order(['total_weight' => 2.5])->refresh()->total_weight - 2.5);
    }

    public function test_harga_snapshot_order_item_tetap(): void
    {
        $service = Service::factory()->perKg()->create(['price' => 6000]);

        $item = OrderItem::factory()->create([
            'service_id' => $service->id,
            'quantity' => 2.0,
            'unit_price' => 6000,
            'subtotal' => 12000,
        ]);

        $service->update(['price' => 9000]);

        $this->assertSame(6000, $item->refresh()->unit_price);
        $this->assertSame(12000, $item->subtotal);
    }
}
