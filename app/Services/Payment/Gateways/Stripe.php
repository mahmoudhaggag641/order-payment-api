<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use Exception;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class Stripe implements PaymentGatewayInterface
{
    private array $config;
    private StripeClient $stripe;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->stripe = new StripeClient($this->config['secret']);
    }

    public function checkout(Payment $payment, array $data): array
    {
        // Create Stripe checkout session
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $this->formatLineItems($payment->order),
            'mode' => 'payment',
            'success_url' => $data['success_url'] ?? route('payment.redirect', ['status' => 'success', 'id' => $payment->uuid]),
            'cancel_url' => $data['cancel_url'] ?? route('payment.redirect', ['status' => 'cancel', 'id' => $payment->uuid]),
            'client_reference_id' => $payment->uuid,
            'metadata' => [
                'order_uuid' => $payment->order->uuid,
                'payment_uuid' => $payment->uuid,
            ],
            'currency' => $this->config['currency'] ?? 'usd',
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'data' => $session->toArray(),
        ];
    }

    public function handleCallback(Request $request): Payment
    {
        $payload = $request->getContent();

        if (!$this->verifySignature($request)) {
            throw new \Exception('Invalid Stripe signature');
        }

        $event = json_decode($payload, true);

        return $this->handleStripeEvent($event);
    }

    public function verifySignature(Request $request): bool
    {
        try {
            $payload = $request->getContent();
            $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];

            Webhook::constructEvent(
                $payload,
                $signature,
                $this->config['webhook_secret']
            );

            return true;
        } catch (SignatureVerificationException $e) {
            return false;
        }
    }

    private function formatLineItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'price_data' => [
                    'currency' => $this->config['currency'] ?? 'usd',
                    'product_data' => [
                        'name' => $item->product_name,
                    ],
                    'unit_amount' => (int) ($item->price * 100), // Convert to cents
                ],
                // 'price' => 'price_id_of_stripe',
                'quantity' => $item->quantity,
            ];
        })->toArray();
    }

    private function handleStripeEvent(array $event): Payment
    {
        $response = array_merge($payment->gateway_response ?? [], ['stripe_event' => $event]);

        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $payment = Payment::where('uuid', $session['client_reference_id'])->firstOrFail();

                if ($session['payment_status'] === 'paid') $payment->markAsSuccessful($response);
                break;
            case 'checkout.session.expired':
                $session = $event['data']['object'];
                $payment = Payment::where('uuid', $session['client_reference_id'])->firstOrFail();

                $payment->markAsFailed($response);
                break;
        }

        return $payment;
    }
}
