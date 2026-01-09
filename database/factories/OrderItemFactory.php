<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $productNames = [
            'Laptop Pro',
            'iPhone 15 Pro',
            'iPad Air',
            'Headphones',
            'AirPods Pro'
        ];

        $productName = $this->faker->randomElement($productNames);

        return [
            'order_id' => Order::factory(),
            'product_name' => $productName,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => $order->id,
        ]);
    }
}
