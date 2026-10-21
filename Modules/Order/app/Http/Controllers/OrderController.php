<?php

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Http\Requests\ListOrdersRequest;
use Modules\Order\Http\Requests\StoreOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderStatusRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends BaseApiController
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $orders = $this->orders->list($request->user(), $request->validated());

        return $this->fromResource(OrderResource::collection($orders))
            ->addMessageToResponse('Orders retrieved successfully.')
            ->toResponse();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);
        $order = $this->orders->create($request->user(), $request->validated());

        return $this->successResponse(
            'Order created successfully.',
            ['data' => new OrderResource($order)],
            Response::HTTP_CREATED,
        );
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->successResponse(
            'Order retrieved successfully.',
            ['data' => new OrderResource($this->orders->show($order))],
        );
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        return $this->successResponse(
            'Order updated successfully.',
            ['data' => new OrderResource($this->orders->update($order, $request->validated()))],
        );
    }

    public function changeStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);
        $status = OrderStatus::from($request->validated('status'));

        return $this->successResponse(
            'Order status updated successfully.',
            ['data' => new OrderResource($this->orders->changeStatus($order, $status))],
        );
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $this->orders->delete($order);

        return $this->successResponse('Order deleted successfully.');
    }
}
