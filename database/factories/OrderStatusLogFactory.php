<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusLog>
 */
class OrderStatusLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = OrderStatusLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => OrderStatus::Received,
            'changed_by' => User::factory(),
            'note' => null,
            'created_at' => now(),
        ];
    }
}
