<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;

/**
 * Hak akses pesanan sesuai tabel di architecture.md bagian 4.
 */
class OrderPolicy
{
    /**
     * Daftar pesanan: admin dan owner.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Lihat detail: admin/owner semua, pelanggan hanya pesanannya sendiri.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $order->customer?->user_id === $user->id;
    }

    /**
     * Membuat pesanan: admin/kasir untuk walk-in, pelanggan untuk online.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Ubah data/item pesanan: hanya admin/kasir.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->isStaff() && ! $this->isLocked($order);
    }

    /**
     * Ubah status: hanya admin/kasir.
     */
    public function changeStatus(User $user, Order $order): bool
    {
        return $user->isStaff() && ! $this->isLocked($order);
    }

    /**
     * Mundurkan status: hanya pemilik.
     */
    public function rollbackStatus(User $user, Order $order): bool
    {
        return $user->isOwner() && ! $this->isLocked($order);
    }

    /**
     * Batalkan pesanan: admin/kasir (sebelum Dicuci), pelanggan hanya saat
     * pesanannya sendiri masih Menunggu Konfirmasi.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return ! $this->isLocked($order);
        }

        return $order->customer?->user_id === $user->id
            && $order->status === OrderStatus::PendingConfirmation;
    }

    /**
     * Catat pembayaran: hanya admin/kasir.
     */
    public function recordPayment(User $user, Order $order): bool
    {
        return $user->isStaff() && ! $this->isLocked($order);
    }

    /**
     * Pesanan yang sudah Diambil/Lunas atau Dibatalkan terkunci.
     */
    private function isLocked(Order $order): bool
    {
        return $order->status->isFinal()
            || ($order->status === OrderStatus::PickedUp && $order->payment_status === PaymentStatus::Paid);
    }
}
