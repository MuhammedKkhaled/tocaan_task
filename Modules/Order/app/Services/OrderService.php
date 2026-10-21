<?php

namespace Modules\Order\Services;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\Currency;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Contracts\OrderRepositoryInterface;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Exceptions\InvalidOrderStatusTransitionException;
use Modules\Order\Exceptions\OrderHasPaymentsException;
use Modules\Order\Exceptions\OrderNotPendingException;
use Modules\Order\Exceptions\OrderTotalExceededException;
use Modules\Order\Models\Order;

class OrderService
{
    private const string MAX_TOTAL = '9999999999.99';

    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ReferenceGenerator $references,
    ) {
    }

    /**
     * @param  array{status?: string, per_page?: int, cursor?: string}  $filters
     * @return CursorPaginator<int, Order>
     */
    public function list(User $user, array $filters): CursorPaginator
    {
        $status = isset($filters['status'])
            ? OrderStatus::from($filters['status'])
            : null;

        return $this->orders->listForUser($user, $status, $filters['per_page'] ?? 15);
    }

    public function show(Order $order): Order
    {
        return $this->orders->withItems($order);
    }

    /** @param array<string, mixed> $attributes */
    public function create(User $user, array $attributes): Order
    {
        return DB::transaction(function () use ($user, $attributes): Order {
            [$items, $total] = $this->prepareItems($attributes['items']);

            $order = $this->orders->createForUser($user, [
                'number' => $this->references->orderNumber(),
                'status' => OrderStatus::Pending,
                'total_amount' => $total,
                'currency' => $attributes['currency'] ?? Currency::USD,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $this->orders->replaceItems($order, $items);

            return $this->orders->withItems($order);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Order $order, array $attributes): Order
    {
        return DB::transaction(function () use ($order, $attributes): Order {
            $order = $this->orders->lock($order);

            if ($order->status !== OrderStatus::Pending) {
                throw new OrderNotPendingException();
            }

            $orderAttributes = array_intersect_key($attributes, array_flip(['notes', 'currency']));

            if (isset($attributes['items'])) {
                [$items, $total] = $this->prepareItems($attributes['items']);
                $orderAttributes['total_amount'] = $total;
                $this->orders->replaceItems($order, $items);
            }

            if ($orderAttributes !== []) {
                $order = $this->orders->updateOrder($order, $orderAttributes);
            }

            return $this->orders->withItems($order);
        });
    }

    public function changeStatus(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status): Order {
            $order = $this->orders->lock($order);

            if (! $order->status->canTransitionTo($status)) {
                throw new InvalidOrderStatusTransitionException($order->status, $status);
            }

            $order = $this->orders->updateOrder($order, ['status' => $status]);

            return $this->orders->withItems($order);
        });
    }

    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = $this->orders->lock($order);

            if ($this->orders->hasPayments($order)) {
                throw new OrderHasPaymentsException();
            }

            $this->orders->deleteOrder($order);
        });
    }

    /**
     * @param  list<array{product_name: string, quantity: int, price: int|float|string}>  $items
     * @return array{0: list<array{product_name: string, quantity: int, unit_price: string, subtotal: string}>, 1: string}
     */
    private function prepareItems(array $items): array
    {
        $prepared = [];
        $total = '0.00';

        foreach ($items as $item) {
            $unitPrice = bcround((string) $item['price'], 2);
            $subtotal = bcmul($unitPrice, (string) $item['quantity'], 2);
            $total = bcadd($total, $subtotal, 2);

            if (bccomp($total, self::MAX_TOTAL, 2) === 1) {
                throw new OrderTotalExceededException();
            }

            $prepared[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        return [$prepared, $total];
    }
}
