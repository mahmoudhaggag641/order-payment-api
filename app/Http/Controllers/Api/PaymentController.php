<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentRequest;
use App\Repositories\OrderRepository;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(public OrderRepository $orderRepo) {}

    public function charge(PaymentRequest $request)
    {
        try {
            $order = $this->orderRepo->findByUuid($request->order_id);

            // Check if order is confirmed
            if (! $order->canProcessPayment()) {
                return ApiResponse::error('Order cannot be processed for payment, please contact support team to confirm it.');
            }

            $paymentService = new PaymentService($request->gateway);

            $result = $paymentService->charge($order, $request->all());

            return ApiResponse::success($result, 'Payment initiated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function webhook(Request $request, string $gateway)
    {
        $paymentService = new PaymentService($gateway);

        $payment = $paymentService->handleWebhook($request);

        return ApiResponse::success($payment, 'Webhook processed successfully');
    }

    public function success(Request $request)
    {
        return ApiResponse::success(true, 'Payment successful');
    }

    public function cancel(Request $request)
    {
        return ApiResponse::error('Payment canceled');
    }
}
