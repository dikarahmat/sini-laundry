<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Format tampilan uang dan tanggal Bahasa Indonesia (style-guide.md bagian 10).
 * Uang selalu disimpan sebagai integer rupiah.
 */
class Money
{
    /**
     * Format rupiah: 25000 menjadi "Rp 25.000".
     */
    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Format tanggal dan waktu: "14 Okt 2026, 15:30 WIB".
     */
    public static function datetime(?CarbonInterface $value): string
    {
        if ($value === null) {
            return '-';
        }

        return Carbon::instance($value)
            ->timezone(config('app.timezone'))
            ->translatedFormat('j M Y, H:i').' WIB';
    }

    /**
     * Format tanggal saja: "14 Okt 2026".
     */
    public static function date(?CarbonInterface $value): string
    {
        if ($value === null) {
            return '-';
        }

        return Carbon::instance($value)
            ->timezone(config('app.timezone'))
            ->translatedFormat('j M Y');
    }

    /**
     * Bobot dalam kg: 1.5 menjadi "1,5 kg".
     */
    public static function weight(float $kg): string
    {
        return rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',').' kg';
    }
}
