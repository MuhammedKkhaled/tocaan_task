<?php

namespace Modules\Order\Contracts;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Modules\Core\Contracts\BaseRepositoryInterface;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /** @return CursorPaginator<int, Order> */
    public function listForUser(User $user, ?OrderStatus $status, int $perPage): CursorPaginator;

    /** @param array<string, mixed> $attributes */
    public function createForUser(User $user, array $attributes): Order;

    /** @param array<string, mixed> $attributes */
    public function updateOrder(Order $order, array $attributes): Order;

    /** @param list<array<string, mixed>> $items */
    public function replaceItems(Order $order, array $items): void;

    public function withItems(Order $order): Order;

    public function lock(Order $order): Order;

    public function hasPayments(Order $order): bool;

    public function deleteOrder(Order $order): bool;
}
