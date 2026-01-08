<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private PaymentGatewayInterface $gateway;

    public function __construct(string $gateway)
    {
        $this->gateway = PaymentGatewayResolver::make($gateway);
    }

    public function charge(Order $order, array $data): array
    {
        return DB::transaction(function () use ($order, $data) {
            $this->gateway->charge($order, $data);
        });
    }

    public function handleWebhook(Request $request): Payment
    {
        return $this->gateway->handleCallback($request);
    }
}
