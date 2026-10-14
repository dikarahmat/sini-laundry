<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => 8000,
            'method' => PaymentMethod::Cash,
            'received_by' => User::factory(),
            'paid_at' => now(),
        ];
    }

    /**
     * Pembayaran transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => PaymentMethod::Transfer,
        ]);
    }
}
