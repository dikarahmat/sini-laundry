<?php

namespace App\Enums;

/**
 * Status pembayaran pesanan (architecture.md bagian 5.2).
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Partial => 'DP',
            self::Paid => 'Lunas',
        };
    }
}
