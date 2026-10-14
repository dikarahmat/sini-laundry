<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use App\Support\Phone;
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

        if ($user->customer !== null) {
            return;
        }

        $phone = Phone::normalize($user->phone);

        $walkIn = $phone !== null
            ? Customer::query()
                ->whereNull('user_id')
                ->where('phone', $phone)
                ->first()
            : null;

        if ($walkIn !== null) {
            $walkIn->update(['user_id' => $user->id]);
            $user->setRelation('customer', $walkIn);

            return;
        }

        Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $phone,
        ]);
    }
}
