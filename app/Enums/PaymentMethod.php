<?php

namespace App\Enums;

/**
 * Metode pembayaran (architecture.md bagian 5.2).
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qris = 'qris';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer',
            self::Qris => 'QRIS',
        };
    }
}
