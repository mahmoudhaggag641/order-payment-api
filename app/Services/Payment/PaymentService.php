<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private PaymentGatewayInterface $gateway;
    private PaymentRepository $repo;

    public function __construct(string $gateway)
    {
        $this->gateway = PaymentGatewayResolver::make($gateway);
        $this->repo = new PaymentRepository();
    }

    public function checkout(Order $order, array $data): array
    {
        return DB::transaction(function () use ($order, $data) {
            $payment = $this->repo->create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'gateway' => $data['gateway'],
            ]);

            $response = $this->gateway->checkout($payment, $data);

            $this->repo->updateGatewayResponse($payment, $response);

            return [
                'payment_id' => $payment->uuid,
                'id' => $response['id'],
                'url' => $response['url'],
            ];
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
