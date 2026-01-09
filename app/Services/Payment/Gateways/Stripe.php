<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentGatewayInterface;
use Exception;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class Stripe implements PaymentGatewayInterface
{
    private StripeClient $stripe;
    private PaymentRepository $repo;
    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.stripe.config', []);
        $this->stripe = new StripeClient($this->config['secret']);
        $this->repo = new PaymentRepository();
    }

    public function checkout(Order $order, array $data): array
    {
        // Create payment record
        $payment = $this->repo->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'gateway' => 'stripe',
        ]);

        // Create Stripe checkout session
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $this->formatLineItems($order),
            'mode' => 'payment',
            'success_url' => $data['success_url'] ?? route('payment.redirect', ['status' => 'success', 'id' => $payment->uuid]),
            'cancel_url' => $data['cancel_url'] ?? route('payment.redirect', ['status' => 'cancel', 'id' => $payment->uuid]),
            'client_reference_id' => $payment->uuid,
            'metadata' => [
                'order_uuid' => $order->uuid,
                'payment_uuid' => $payment->uuid,
            ],
            'currency' => $this->config['currency'] ?? 'usd',
        ]);

        $payment->update([
            'gateway_response' => [
                'session_id' => $session->id,
                'session_data' => $session->toArray()
            ]
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
            'payment_uuid' => $payment->uuid,
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
        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $payment = $this->repo->findByUuid($session['client_reference_id']);

                if ($session['payment_status'] === 'paid') {
                    $payment->markAsSuccessful(array_merge(
                        $payment->gateway_response ?? [],
                        ['stripe_event' => $event]
                    ));
                }

                return $payment;

            case 'checkout.session.expired':
                $session = $event['data']['object'];
                $payment = $this->repo->findByUuid($session['client_reference_id']);

                $payment->markAsFailed(array_merge(
                    $payment->gateway_response ?? [],
                    ['stripe_event' => $event]
                ));

                return $payment;

            default:
                throw new \Exception('Unhandled event type');
        }
    }
}
