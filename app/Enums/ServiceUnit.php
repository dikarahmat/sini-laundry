<?php

namespace App\Enums;

/**
 * Satuan layanan laundry (architecture.md bagian 5.2).
 */
enum ServiceUnit: string
{
    case Kilogram = 'kg';
    case Piece = 'pcs';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Kilogram => 'per kg',
            self::Piece => 'per pcs',
        };
    }
}
