<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_fails_for_unconfirmed_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'visa',
            'gateway' => 'credit_card',
            'payment_details' => []
        ]);

        $response->assertStatus(400);
    }
}
