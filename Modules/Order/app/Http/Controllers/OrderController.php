<?php

namespace Modules\Order\Http\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Core\Http\Documentation\ApiErrorResponse;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Http\Requests\ListOrdersRequest;
use Modules\Order\Http\Requests\StoreOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderStatusRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Symfony\Component\HttpFoundation\Response;

#[Group('Orders')]
class OrderController extends BaseApiController
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    /** List the authenticated user's orders. */
    #[ApiResponse(description: 'Orders retrieved', type: 'array{message: string, data: list<OrderResource>, meta: array<string, mixed>}', examples: [['message' => 'Orders retrieved successfully.', 'data' => [], 'meta' => ['items_Per_Page' => 15, 'Items_in_page' => 0, 'next_cursor' => null, 'previous_cursor' => null, 'next_pageUrl' => null, 'previous_pageUrl' => null]]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function index(ListOrdersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $orders = $this->orders->list($request->user(), $request->validated());

        return $this->fromResource(OrderResource::collection($orders))
            ->addMessageToResponse('Orders retrieved successfully.')
            ->toResponse();
    }

    /** Create an order and calculate its total. */
    #[ApiResponse(status: 201, description: 'Order created', type: 'array{message: string, data: OrderResource}', examples: [['message' => 'Order created successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
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

    /** Retrieve one order. */
    #[ApiResponse(description: 'Order retrieved', type: 'array{message: string, data: OrderResource}', examples: [['message' => 'Order retrieved successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(429)]
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->successResponse(
            'Order retrieved successfully.',
            ['data' => new OrderResource($this->orders->show($order))],
        );
    }

    /** Update a pending order. */
    #[ApiResponse(description: 'Order updated', type: 'array{message: string, data: OrderResource}', examples: [['message' => 'Order updated successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(409)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        return $this->successResponse(
            'Order updated successfully.',
            ['data' => new OrderResource($this->orders->update($order, $request->validated()))],
        );
    }

    /** Transition an order to another status. */
    #[ApiResponse(description: 'Order status updated', type: 'array{message: string, data: OrderResource}', examples: [['message' => 'Order status updated successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(409)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function changeStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);
        $status = OrderStatus::from($request->validated('status'));

        return $this->successResponse(
            'Order status updated successfully.',
            ['data' => new OrderResource($this->orders->changeStatus($order, $status))],
        );
    }

    /** Delete an order that has no payments. */
    #[ApiResponse(description: 'Order deleted', type: 'array{message: string}', examples: [['message' => 'Order deleted successfully.']])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(409)]
    #[ApiErrorResponse(429)]
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $this->orders->delete($order);

        return $this->successResponse('Order deleted successfully.');
    }
}
