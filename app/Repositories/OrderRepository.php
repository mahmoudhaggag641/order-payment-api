<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Helpers\ApiResponse;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    public function query($query, array $params = [])
    {
        $status = gv($params, 'status');

        $query->when($status, function ($q, $status) {
            $q->where('status', $status);
        });
    }

    public function transform($paginator)
    {
        return $paginator->through(fn($order) => new OrderResource($order));
    }

    public function formatParams($params, $order = null): array
    {
        $formatted = [
            'metadata' => gv($params, 'metadata'),
        ];

        if (! $order) {
            $formatted['user_id'] = auth()->id();
            $formatted['status'] = OrderStatus::PENDING;
        }

        return $formatted;
    }

    public function setRelations($order, array $params)
    {
        $this->setItems($order, $params);

        $order->updateTotal();
    }

    public function canDelete($order)
    {
        if (! $order->canBeDeleted()) {
            throw new HttpResponseException(ApiResponse::error('Cannot delete order with associated payments'));
        }
    }

    private function setItems(Order $order, array $params)
    {
        $items = gv($params, 'items', []);

        foreach ($items as $itemData) {
            if (isset($itemData['id'])) { // Update existing item
                $orderItem = $order->items()->where('id', $itemData['id'])->first();
                if ($orderItem) $orderItem->update($itemData);
            } else { // Create new item
                $order->items()->create($itemData);
            }
        }
    }
}
