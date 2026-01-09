<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_create_order(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 10.50,
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'uuid', 'total', 'status', 'metadata', 'created_at', 'updated_at', 'items']
            ]);
    }

    public function test_user_can_view_their_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/orders");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJsonCount(3, 'data.data');
    }

    public function test_user_cannot__delete_order_with_associated_payments(): void
    {
        $order = Order::factory()->withPayments()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/orders/{$order->uuid}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete order with associated payments'
            ]);
    }
}
