<?php

namespace App\Models;

use App\Support\Phone;
use Carbon\CarbonInterface;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $phone
 * @property string|null $address
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
    ];

    /**
     * Akun pengguna pelanggan (null jika walk-in).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pesanan milik pelanggan ini.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Pastikan user punya baris pelanggan (buat atau tautkan ke walk-in).
     * Dipakai saat registrasi, login pertama, dan saat membuat pesanan online.
     */
    public static function ensureForUser(User $user): self
    {
        $existing = $user->customer;

        if ($existing !== null) {
            return $existing;
        }

        $phone = Phone::normalize($user->phone);

        $walkIn = $phone !== null
            ? static::query()->whereNull('user_id')->where('phone', $phone)->first()
            : null;

        if ($walkIn !== null) {
            $walkIn->update(['user_id' => $user->id]);
            $user->setRelation('customer', $walkIn);

            return $walkIn;
        }

        $customer = static::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $phone,
        ]);

        $user->setRelation('customer', $customer);

        return $customer;
    }
}
