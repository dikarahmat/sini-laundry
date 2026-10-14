<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Aturan perpindahan status pesanan (PRD.md bagian 7):
 * - hanya boleh maju sesuai urutan
 * - mundur hanya role owner dengan catatan wajib
 * - pembatalan hanya sebelum status Dicuci
 * - pesanan yang sudah Diambil/Lunas tidak boleh diubah
 * - setiap perubahan membuat baris order_status_logs dalam satu transaksi
 */
class OrderStatusService
{
    /**
     * Maju ke status berikutnya dalam alur.
     */
    public function advance(Order $order, User $actor): Order
    {
        $this->assertChangeable($order);

        $next = $order->status->next();

        if ($next === null) {
            throw new RuntimeException('Pesanan sudah berada di status akhir.');
        }

        return $this->apply($order, $next, $actor, null);
    }

    /**
     * Mundurkan status ke tahap sebelumnya (hanya owner, catatan wajib).
     */
    public function rollback(Order $order, OrderStatus $target, User $actor, string $note): Order
    {
        $this->assertChangeable($order);

        if (! $actor->isOwner()) {
            throw new AuthorizationException('Hanya pemilik yang boleh mundurkan status pesanan.');
        }

        if (trim($note) === '') {
            throw ValidationException::withMessages([
                'note' => 'Catatan wajib diisi saat mundurkan status pesanan.',
            ]);
        }

        if ($target === $order->status || ! $target->isBefore($order->status)) {
            throw new RuntimeException('Status tujuan mundur harus lebih awal dari status sekarang.');
        }

        return $this->apply($order, $target, $actor, $note);
    }

    /**
     * Batalkan pesanan (hanya sebelum status Dicuci).
     */
    public function cancel(Order $order, User $actor, ?string $note = null): Order
    {
        $this->assertChangeable($order);

        if (! $order->status->canBeCancelled()) {
            throw new RuntimeException('Pesanan hanya bisa dibatalkan sebelum status Dicuci.');
        }

        return $this->apply($order, OrderStatus::Cancelled, $actor, $note);
    }

    /**
     * Terapkan status baru beserta log-nya dalam satu transaksi.
     */
    private function apply(Order $order, OrderStatus $status, User $actor, ?string $note): Order
    {
        return DB::transaction(function () use ($order, $status, $actor, $note): Order {
            $order->update([
                'status' => $status,
                'picked_up_at' => $status === OrderStatus::PickedUp ? now() : null,
            ]);

            $order->statusLogs()->create([
                'status' => $status,
                'changed_by' => $actor->id,
                'note' => $note,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Pesanan final (Diambil/Dibatalkan) atau sudah Lunas tidak boleh diubah.
     */
    private function assertChangeable(Order $order): void
    {
        $locked = $order->status->isFinal()
            || ($order->status === OrderStatus::PickedUp && $order->payment_status === PaymentStatus::Paid);

        if ($locked) {
            throw new RuntimeException('Pesanan yang sudah Diambil dan Lunas, atau Dibatalkan, tidak bisa diubah lagi.');
        }
    }
}
