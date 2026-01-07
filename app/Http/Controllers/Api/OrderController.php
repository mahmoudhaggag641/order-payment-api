<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Repositories\OrderRepository;

class OrderController extends Controller
{
    public function __construct(public OrderRepository $repo) {}

    /**
     * @LRDparam page integer|default:1
     * @LRDparam per_page nullable|integer|default:10
     * @LRDparam sort_by nullable|string|default:created_at
     * @LRDparam sort_order nullable|string|in:asc,desc|default:desc
     * @LRDparam user_id nullable|integer|default:auth()->id()
     * @LRDparam status nullable|string|in:pending,confirmed,cancelled
     */
    public function index()
    {
        $orders = $this->repo->paginate(request()->all());

        return ApiResponse::success($orders, 'Orders retrieved successfully');
    }

    public function store(OrderRequest $request)
    {
        $order = $this->repo->create($request->all());

        return ApiResponse::created(new OrderResource($order), 'Order created successfully');
    }

    public function show(string $id)
    {
        $order = $this->repo->findByUuid($id);

        return ApiResponse::success(new OrderResource($order), 'Order retrieved successfully');
    }

    public function update(OrderRequest $request, string $id)
    {
        $order = $this->repo->findByUuid($id);

        $updated = $this->repo->update($order, $request->all());

        ApiResponse::success($updated, 'Order updated successfully');
    }

    public function destroy(string $id)
    {
        $order = $this->repo->findByUuid($id);

        $this->repo->delete($order);

        return ApiResponse::success(true, 'Order deleted successfully');
    }
}
