<?php

namespace App\Support;

/**
 * Normalisasi dan validasi nomor HP format Indonesia.
 * Satu tempat agar format penyimpanan konsisten (08xxxxxxxxxx).
 */
class Phone
{
    /**
     * Ubah berbagai bentuk nomor (08..., +628..., 628...) ke bentuk 08xxxxxxxxxx.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        if (! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * Nomor HP Indonesia sederhana yang diterima: 08..., +62..., atau 62....
     */
    public static function isValid(?string $phone): bool
    {
        $normalized = self::normalize($phone);

        return $normalized !== null && preg_match('/^08[1-9][0-9]{6,11}$/', $normalized) === 1;
    }

    /**
     * Empat digit terakhir nomor HP, dipakai untuk cek status publik.
     */
    public static function lastFourDigits(?string $phone): string
    {
        $normalized = self::normalize($phone) ?? '';

        return substr($normalized, -4);
    }
}
