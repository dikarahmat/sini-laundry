<?php

namespace App\Enums;

/**
 * Peran pengguna sistem Sini Laundry (sesuai architecture.md bagian 4).
 */
enum Role: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Owner = 'owner';

    /**
     * Label Bahasa Indonesia untuk tampilan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Pelanggan',
            self::Admin => 'Admin',
            self::Owner => 'Pemilik',
        };
    }

    /**
     * Peran yang boleh mengakses area admin (kasir).
     *
     * @return array<int, string>
     */
    public static function staffValues(): array
    {
        return [self::Admin->value, self::Owner->value];
    }
}
