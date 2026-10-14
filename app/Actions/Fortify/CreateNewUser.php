<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Phone::isValid(is_string($value) ? $value : null)) {
                    $fail('Format nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
                }
            }],
            'password' => $this->passwordRules(),
        ], [
            'phone.required' => 'Nomor HP wajib diisi.',
        ])->validate();

        $phone = Phone::normalize($input['phone']);

        // Peran tidak boleh diisi dari form publik: selalu customer.
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $phone,
            'role' => Role::Customer,
            'password' => $input['password'],
        ]);

        // Pastikan pelanggan baru punya baris customers (menautkan walk-in jika ada).
        Customer::ensureForUser($user);

        return $user;
    }
}
