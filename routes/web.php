<?php

use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Cek status publik (tanpa login).
Route::livewire('cek-status', 'pages::cek-status')->name('cek-status');

// Nota/struk cetak (khusus admin dan owner).
Route::get('admin/pesanan/{order}/nota', function (Order $order) {
    $order->load(['customer', 'items.service', 'payments', 'creator']);

    return view('pages.admin.pesanan.nota', ['order' => $order]);
})->middleware(['auth', 'role:admin,owner'])->name('admin.pesanan.nota');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::livewire('pesanan', 'pages::pesanan')->name('pesanan.index');
    Route::livewire('pesanan/baru', 'pages::pesanan.baru')->name('pesanan.baru');
    Route::livewire('pesanan/{order}', 'pages::pesanan.detail')->name('pesanan.show');
});

Route::middleware(['auth', 'role:admin,owner'])->group(function () {
    Route::livewire('admin/pesanan/baru', 'pages::admin.pesanan-baru')->name('admin.pesanan.baru');
    Route::livewire('admin/pesanan', 'pages::admin.pesanan')->name('admin.pesanan.index');
    Route::livewire('admin/pesanan/{order}', 'pages::admin.pesanan.detail')->name('admin.pesanan.show');
    Route::livewire('admin/pelanggan', 'pages::admin.pelanggan')->name('admin.pelanggan.index');
});

Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::livewire('owner/dashboard', 'pages::owner.dashboard')->name('owner.dashboard');
    Route::livewire('owner/layanan', 'pages::owner.layanan')->name('owner.layanan');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
