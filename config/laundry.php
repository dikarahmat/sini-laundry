<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Laundry Sini Laundry
    |--------------------------------------------------------------------------
    |
    | Pengaturan bisnis laundry. Untuk MVP nilainya diatur lewat config ini;
    | antarmuka pengaturan untuk pemilik masuk Fase 2.
    |
    */

    // Tambahan biaya layanan express, dalam persen dari subtotal.
    'express_surcharge_percent' => 50,

    // Berat minimal per pesanan untuk layanan kiloan (kg).
    'min_weight_kg' => 1,

    /*
    |--------------------------------------------------------------------------
    | Identitas Usaha
    |--------------------------------------------------------------------------
    |
    | Dipakai pada nota/struk cetak (T4.2) dan halaman publik.
    |
    */

    'business' => [
        'name' => env('APP_NAME', 'Sini Laundry'),
        'address' => 'Jl. Bersih Raya No. 12, Kota Kamu',
        'phone' => '0812-0000-0000',
    ],

];
