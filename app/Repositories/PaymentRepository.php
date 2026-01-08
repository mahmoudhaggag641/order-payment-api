<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;

class PaymentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Payment());
    }

    public function query($query, array $params = [])
    {
        $status = gv($params, 'status');
        $order_id = gv($params, 'order_id');
        $user_id = (int) (gv($params, 'user_id') ?? auth()->id());

        $query->whereHas('order', function ($q) use ($user_id) {
            $q->where('user_id', $user_id);
        });

        $query->when($status, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($order_id, function ($q, $order_id) {
            $q->where('order_id', $order_id);
        });
    }

    public function transform($paginator)
    {
        return $paginator->through(fn($payment) => new PaymentResource($payment));
    }

    public function formatParams($params, $payment = null): array
    {
        $formatted = [
            'status' => gv($params, 'status', PaymentStatus::PENDING),
            'amount' => gv($params, 'amount'),
            'gateway' => gv($params, 'gateway', config('payment.default_gateway')),
            'gateway_response' => gv($params, 'gateway_response'),
        ];

        if (! $payment) {
            $formatted['order_id'] = gv($params, 'order_id');
        }

        return $formatted;
    }

    public function setRelations($payment, array $params) {}

    public function canDelete($payment) {}
}
