<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPal implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $accessToken;
    private PaymentRepository $repo;
    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.paypal.config', []);
        $this->baseUrl = $this->config['mode'] === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $this->repo = new PaymentRepository();

        $this->authenticate();
    }

    public function charge(Order $order, array $data): array
    {
        // Create payment record
        $payment = $this->repo->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'gateway' => 'paypal',
        ]);

        // Create PayPal order
        $paypalOrder = $this->createPayPalOrder($order, $payment, $data);

        // Update payment with PayPal data
        $payment->update([
            'gateway_response' => [
                'paypal_order_id' => $paypalOrder['id'],
                'paypal_data' => $paypalOrder
            ]
        ]);

        // Find approval URL
        $approveUrl = collect($paypalOrder['links'])
            ->where('rel', 'approve')
            ->first()['href'];

        return [
            'order_id' => $paypalOrder['id'],
            'approval_url' => $approveUrl,
            'payment_uuid' => $payment->uuid,
        ];
    }

    public function handleCallback(Request $request): Payment
    {
        if (!$this->verifySignature($request)) {
            throw new \Exception('Invalid PayPal signature');
        }

        $event = $request->all();

        return $this->handlePayPalEvent($event);
    }

    public function verifySignature(Request $request): bool
    {
        try {
            $transmissionId = $request->header('Paypal-Transmission-Id');
            $transmissionTime = $request->header('Paypal-Transmission-Time');
            $certUrl = $request->header('Paypal-Cert-Url');
            $transmissionSig = $request->header('Paypal-Transmission-Sig');
            $webhookId = $this->config['webhook_id'] ?? '';

            // Verify webhook signature using PayPal API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->post($this->baseUrl . '/v1/notifications/verify-webhook-signature', [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'cert_url' => $certUrl,
                'transmission_sig' => $transmissionSig,
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ]);

            return $response->successful() && $response->json('verification_status') === 'SUCCESS';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function authenticate(): void
    {
        $response = Http::withBasicAuth(
            $this->config['client_id'],
            $this->config['client_secret']
        )->asForm()->post($this->baseUrl . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials'
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to authenticate with PayPal');
        }

        $this->accessToken = $response->json('access_token');
    }

    private function createPayPalOrder(Order $order, Payment $payment, array $data): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
            'PayPal-Request-Id' => $payment->uuid,
        ])->post($this->baseUrl . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $order->uuid,
                    'description' => 'Order #' . $order->id,
                    'custom_id' => $payment->uuid,
                    'amount' => [
                        'currency_code' => $this->config['currency'] ?? 'USD',
                        'value' => number_format($order->total, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $this->config['currency'] ?? 'USD',
                                'value' => number_format($order->total, 2, '.', ''),
                            ]
                        ]
                    ],
                    'items' => $this->formatPayPalItems($order),
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => env('APP_NAME'),
                        'locale' => 'en-US',
                        'landing_page' => 'LOGIN',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $data['return_url'] ?? $this->config['return_url'],
                        'cancel_url' => $data['cancel_url'] ?? $this->config['cancel_url'],
                    ]
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create PayPal order: ' . $response->body());
        }

        return $response->json();
    }

    private function formatPayPalItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'name' => $item->product_name,
                'quantity' => (string) $item->quantity,
                'unit_amount' => [
                    'currency_code' => $this->config['currency'] ?? 'USD',
                    'value' => number_format($item->price, 2, '.', ''),
                ]
            ];
        })->toArray();
    }

    private function handlePayPalEvent(array $event): Payment
    {
        $resource = $event['resource'] ?? [];
        $paymentUuid = $resource['custom_id'] ?? null;

        if (!$paymentUuid) {
            throw new \Exception('Payment UUID not found in PayPal event');
        }

        $payment = $this->repo->findByUuid($paymentUuid);

        switch ($event['event_type']) {
            case 'CHECKOUT.ORDER.APPROVED':
            case 'PAYMENT.CAPTURE.COMPLETED':
                $payment->markAsSuccessful(array_merge(
                    $payment->gateway_response ?? [],
                    ['paypal_event' => $event]
                ));
                break;

            case 'CHECKOUT.ORDER.VOIDED':
            case 'PAYMENT.CAPTURE.DENIED':
                $payment->markAsFailed(array_merge(
                    $payment->gateway_response ?? [],
                    ['paypal_event' => $event]
                ));
                break;
        }

        return $payment;
    }
}
