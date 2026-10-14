<?php

namespace App\Support;

/**
 * Peta warna, ikon, dan label status cucian (style-guide.md bagian 2).
 * Satu tempat agar status-badge dan timeline konsisten.
 * Kelas warna ditulis utuh agar terdeteksi Tailwind saat build.
 */
class OrderStatusStyles
{
    /**
     * Slug status => label, ikon, dan kelas warna.
     *
     * @var array<string, array{label: string, icon: string, classes: string}>
     */
    public const MAP = [
        'pending_confirmation' => [
            'label' => 'Menunggu Konfirmasi',
            'icon' => 'clock',
            'classes' => 'bg-status-pending-confirmation text-status-pending-confirmation-ink',
        ],
        'received' => [
            'label' => 'Diterima',
            'icon' => 'inbox',
            'classes' => 'bg-status-received text-status-received-ink',
        ],
        'washing' => [
            'label' => 'Dicuci',
            'icon' => 'arrow-path',
            'classes' => 'bg-status-washing text-status-washing-ink',
        ],
        'ironing' => [
            'label' => 'Disetrika',
            'icon' => 'fire',
            'classes' => 'bg-status-ironing text-status-ironing-ink',
        ],
        'done' => [
            'label' => 'Selesai',
            'icon' => 'check',
            'classes' => 'bg-status-done text-status-done-ink',
        ],
        'picked_up' => [
            'label' => 'Diambil',
            'icon' => 'shopping-bag',
            'classes' => 'bg-status-picked-up text-status-picked-up-ink',
        ],
        'cancelled' => [
            'label' => 'Dibatalkan',
            'icon' => 'x-mark',
            'classes' => 'bg-status-cancelled text-status-cancelled-ink',
        ],
    ];

    /**
     * Urutan tahap normal (tanua cancelled), sesuai alur PRD.
     *
     * @var array<int, string>
     */
    public const FLOW = [
        'received',
        'washing',
        'ironing',
        'done',
        'picked_up',
    ];

    /**
     * Ambil gaya untuk sebuah status (menerima string atau enum dengan ->value).
     *
     * @return array{label: string, icon: string, classes: string}
     */
    public static function for(string|object|null $status): array
    {
        $key = $status instanceof \BackedEnum ? (string) $status->value : (string) $status;

        return self::MAP[$key] ?? [
            'label' => $key !== '' ? $key : 'Tidak diketahui',
            'icon' => 'question-mark-circle',
            'classes' => 'bg-status-picked-up text-status-picked-up-ink',
        ];
    }
}
