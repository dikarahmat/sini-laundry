<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Pastikan setiap user pelanggan punya baris customers saat login pertama.
 * Jika ada pelanggan walk-in dengan nomor HP sama, akun ditautkan ke baris itu.
 */
class EnsureCustomerExists
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->role !== Role::Customer) {
            return;
        }

        Customer::ensureForUser($user);
    }
}
