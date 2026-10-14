<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pencatatan pembayaran dan perhitungan status bayar (architecture.md bagian 6):
 * - paid jika total pembayaran >= total_price
 * - partial jika ada pembayaran tetapi kurang
 * - unpaid jika belum ada pembayaran
 */
class PaymentService
{
    /**
     * Catat pembayaran baru dan hitung ulang status bayar pesanan.
     */
    public function record(
        Order $order,
        int $amount,
        PaymentMethod $method,
        User $receiver,
        ?Carbon $paidAt = null,
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pembayaran harus lebih dari 0 rupiah.',
            ]);
        }

        return DB::transaction(function () use ($order, $amount, $method, $receiver, $paidAt): Payment {
            $payment = $order->payments()->create([
                'amount' => $amount,
                'method' => $method,
                'received_by' => $receiver->id,
                'paid_at' => $paidAt ?? now(),
            ]);

            $this->recalculate($order);

            return $payment;
        });
    }

    /**
     * Hitung ulang status pembayaran dari seluruh pembayaran pesanan.
     */
    public function recalculate(Order $order): PaymentStatus
    {
        $paidTotal = (int) $order->payments()->sum('amount');

        $status = match (true) {
            $paidTotal <= 0 => PaymentStatus::Unpaid,
            $paidTotal >= $order->total_price => PaymentStatus::Paid,
            default => PaymentStatus::Partial,
        };

        $order->update(['payment_status' => $status]);

        return $status;
    }
}
