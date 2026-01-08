<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Charge the order
     */
    public function charge(Order $order, array $data): array;

    /**
     * Verify webhook signature and
     * Handle webhook callback
     */
    public function handleCallback(Request $request): Payment;
}
