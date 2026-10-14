<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::livewire('pesanan', 'pages::pesanan')->name('pesanan.index');
});

Route::middleware(['auth', 'role:admin,owner'])->group(function () {
    Route::livewire('admin/pesanan', 'pages::admin.pesanan')->name('admin.pesanan.index');
});

Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::livewire('owner/dashboard', 'pages::owner.dashboard')->name('owner.dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
