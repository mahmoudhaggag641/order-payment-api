<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $order = Order::factory()->create();
        $gateway = $this->faker->randomElement(array_keys(config('payment.gateways')));
        $status = $this->faker->randomElement(PaymentStatus::cases());

        return [
            'uuid' => Str::uuid(),
            'order_id' => $order->id,
            'amount' => $order->total,
            'status' => $status->value,
            'gateway' => $gateway,
            'gateway_response' => $this->generateGatewayResponse($gateway, $status),
            'metadata' => [
                'notes' => $this->faker->optional()->sentence(),
            ],
            'processed_at' => $status !== PaymentStatus::PENDING ? now() : null,
            'created_at' => $this->faker->dateTimeBetween($order->created_at, 'now'),
            'updated_at' => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => $order->id,
            'amount' => $order->total,
        ]);
    }

    private function generateGatewayResponse($gateway, PaymentStatus $status): array
    {
        $baseResponse = [
            'id' => $this->faker->randomNumber(),
            'status' => $status->value,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'usd',
            'created' => now()->timestamp,
        ];

        return match ($gateway) {
            'stripe' => array_merge($baseResponse, []),
            'paypal' => array_merge($baseResponse, []),
            default => $baseResponse,
        };
    }
}
