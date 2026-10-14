<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun contoh untuk development.
     *
     * PERINGATAN: password di bawah HANYA untuk development lokal.
     * Jangan pernah memakai kredensial ini di lingkungan produksi.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Bu Sari (Pemilik)',
                'email' => 'owner@sinilaundry.test',
                'phone' => '081200000001',
                'role' => Role::Owner,
            ],
            [
                'name' => 'Pak Budi (Admin)',
                'email' => 'admin@sinilaundry.test',
                'phone' => '081200000002',
                'role' => Role::Admin,
            ],
            [
                'name' => 'Rina Pelanggan',
                'email' => 'pelanggan@sinilaundry.test',
                'phone' => '081200000003',
                'role' => Role::Customer,
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'role' => $account['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
