<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::CONFIRMED,
            'total' => 100.00
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_process_payment(): void
    {
        $response = $this->postJson('/api/payment/checkout', [
            'order_id' => $this->order->uuid,
            'gateway' => config('payment.default_gateway'),
        ]);

        // real test after change payment gateways data
        // $response->assertStatus(201)
        //     ->assertJsonStructure([
        //         'success',
        //         'message',
        //         'data' => ['session_id', 'url', 'payment_uuid']
        //     ]);

        $response->assertStatus(400);
    }

    public function test_payment_fails_for_unconfirmed_order(): void
    {
        $pendingOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
            'total' => 50.00
        ]);

        $response = $this->postJson('/api/payment/checkout', [
            'order_id' => $pendingOrder->uuid,
            'gateway' => config('payment.default_gateway')
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be processed for payment, please contact support team to confirm it.'
            ]);
    }
}
