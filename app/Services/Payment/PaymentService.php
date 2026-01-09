<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
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

    public function checkout(Order $order, array $data): array
    {
        return DB::transaction(function () use ($order, $data) {
            $this->gateway->checkout($order, $data);
        });
    }

    public function handleWebhook(Request $request): Payment
    {
        $payment = $this->gateway->handleCallback($request);

        $payment->refresh();
        if ($payment->status == PaymentStatus::SUCCESSFUL) {
            // Send confirm email or notification
        }

        return $payment;
    }
}
