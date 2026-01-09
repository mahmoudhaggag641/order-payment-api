<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create checkout the order
     */
    public function checkout(Payment $payment, array $data): array;

    /**
     * Verify webhook signature and
     * Handle webhook callback
     */
    public function handleCallback(Request $request): Payment;
}
