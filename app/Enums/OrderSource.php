<?php

namespace App\Enums;

/**
 * Sumber pesanan: walk-in (dibuat admin) atau online (dibuat pelanggan).
 */
enum OrderSource: string
{
    case WalkIn = 'walk_in';
    case Online = 'online';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Walk-in (kasir)',
            self::Online => 'Online (pelanggan)',
        };
    }
}
