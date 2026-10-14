<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property int $customer_id
 * @property int|null $created_by
 * @property OrderSource $source
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property float $total_weight
 * @property int $total_price
 * @property string|null $notes
 * @property CarbonInterface|null $estimated_done_at
 * @property CarbonInterface|null $picked_up_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'customer_id',
        'created_by',
        'source',
        'status',
        'payment_status',
        'total_weight',
        'total_price',
        'notes',
        'estimated_done_at',
        'picked_up_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => OrderSource::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'total_weight' => 'decimal:2',
            'total_price' => 'integer',
            'estimated_done_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }

    /**
     * Pelanggan pemilik pesanan.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Admin/kasir yang membuat pesanan (null untuk pesanan online).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Item layanan dalam pesanan.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Riwayat perubahan status.
     *
     * @return HasMany<OrderStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest('id');
    }

    /**
     * Pembayaran yang tercatat.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Sisa tagihan yang belum dibayar.
     */
    public function remainingAmount(): int
    {
        return max(0, $this->total_price - (int) $this->payments()->sum('amount'));
    }
}
