<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'total' => $this->faker->randomFloat(2, 10, 1000),
            'status' => $this->faker->randomElement(OrderStatus::cases())->value,
            'metadata' => [
                'notes' => $this->faker->optional()->sentence(),
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($order) {
            // Create 1-5 order items for each order
            \App\Models\OrderItem::factory()
                ->count($this->faker->numberBetween(1, 5))
                ->create(['order_id' => $order->id]);

            // Recalculate total based on items
            $order->update(['total' => $order->items()->sum(DB::raw('quantity * price'))]);
        });
    }

    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function ($order) use ($count) {
            \App\Models\OrderItem::factory($count)->create(['order_id' => $order->id]);
            $order->update(['total' => $order->items()->sum(DB::raw('quantity * price'))]);
        });
    }

    public function withPayments(int $count = 1): static
    {
        return $this->afterCreating(function ($order) use ($count) {
            \App\Models\Payment::factory($count)->create([
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        });
    }
}
