<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = Service::factory()->perKg();
        $price = 8000;
        $quantity = 2.0;

        return [
            'order_id' => Order::factory(),
            'service_id' => $service,
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => (int) round($quantity * $price),
        ];
    }
}
