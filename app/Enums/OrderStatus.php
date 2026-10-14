<?php

namespace App\Enums;

use App\Support\OrderStatusStyles;

/**
 * Status pesanan laundry (architecture.md bagian 5.3).
 * Urutan: pending_confirmation → received → washing → ironing → done → picked_up,
 * serta cancelled.
 */
enum OrderStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Received = 'received';
    case Washing = 'washing';
    case Ironing = 'ironing';
    case Done = 'done';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return OrderStatusStyles::for($this)['label'];
    }

    /**
     * Posisi status dalam alur normal (kecil = lebih awal).
     * cancelled diberi posisi besar karena di luar alur.
     */
    public function position(): int
    {
        return match ($this) {
            self::PendingConfirmation => 0,
            self::Received => 1,
            self::Washing => 2,
            self::Ironing => 3,
            self::Done => 4,
            self::PickedUp => 5,
            self::Cancelled => 90,
        };
    }

    /**
     * Status berikutnya dalam alur normal, null jika sudah di akhir.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::PendingConfirmation => self::Received,
            self::Received => self::Washing,
            self::Washing => self::Ironing,
            self::Ironing => self::Done,
            self::Done => self::PickedUp,
            self::PickedUp, self::Cancelled => null,
        };
    }

    /**
     * Status pesanan yang sudah final dan tidak boleh diubah lagi.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PickedUp, self::Cancelled], true);
    }

    /**
     * Boleh dibatalkan (hanya sebelum Dicuci).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PendingConfirmation, self::Received], true);
    }

    /**
     * Pengalihan maju satu langkah sesuai alur diizinkan?
     */
    public function canAdvanceTo(self $target): bool
    {
        if ($this->isFinal()) {
            return false;
        }

        return $target === $this->next();
    }

    /**
     * Apakah status ini lebih awal dari status lain dalam alur?
     */
    public function isBefore(self $other): bool
    {
        return $this->position() < $other->position();
    }
}
