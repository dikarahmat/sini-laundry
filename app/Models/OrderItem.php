<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $service_id
 * @property float $quantity
 * @property int $unit_price
 * @property int $subtotal
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Tabel ini tidak menyimpan updated_at.
     */
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'service_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * Pesanan pemilik item.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Layanan yang dipilih (harga snapshot tersimpan di unit_price).
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
