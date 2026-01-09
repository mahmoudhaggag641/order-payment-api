<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentRequest;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        public PaymentRepository $repo,
        public OrderRepository $orderRepo
    ) {}

    /**
     * @LRDparam page integer|default:1
     * @LRDparam per_page nullable|integer|default:10
     * @LRDparam sort_by nullable|string|default:created_at
     * @LRDparam sort_order nullable|string|in:asc,desc|default:desc
     * @LRDparam user_id nullable|integer|default:auth()->id()
     * @LRDparam order_id nullable|integer|exists:orders,id
     * @LRDparam status nullable|string|in:pending,successful,failed
     */
    public function index()
    {
        $payments = $this->repo->paginate(request()->all());

        return ApiResponse::success($payments, 'Payments retrieved successfully');
    }

    public function checkout(PaymentRequest $request)
    {
        try {
            $order = $this->orderRepo->findByUuid($request->order_id);

            // Check if order is confirmed
            if (! $order->canProcessPayment()) {
                return ApiResponse::error('Order cannot be processed for payment, please contact support team to confirm it.');
            }

            $paymentService = new PaymentService($request->gateway);

            $result = $paymentService->checkout($order, $request->all());

            return ApiResponse::created($result, 'Payment initiated successfully');
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

    public function redirect($status, $id = null)
    {
        if ($status == 'success') {
            return ApiResponse::success(true, 'Payment successful');
        } else {
            return ApiResponse::error('Payment canceled');
        }
    }
}
