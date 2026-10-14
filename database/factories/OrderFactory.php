<?php

namespace Database\Factories;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SL-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'source' => OrderSource::WalkIn,
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Unpaid,
            'total_weight' => 1.0,
            'total_price' => 8000,
            'notes' => null,
            'estimated_done_at' => now()->addHours(48),
            'picked_up_at' => null,
        ];
    }

    /**
     * Pesanan online yang menunggu konfirmasi admin.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => OrderSource::Online,
            'status' => OrderStatus::PendingConfirmation,
            'created_by' => null,
        ]);
    }

    /**
     * Tetapkan status pesanan.
     */
    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'picked_up_at' => $status === OrderStatus::PickedUp ? now() : null,
        ]);
    }

    /**
     * Pesanan lunas.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => PaymentStatus::Paid,
        ]);
    }
}
