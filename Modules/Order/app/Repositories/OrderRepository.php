<?php

namespace Modules\Order\Repositories;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Order\Contracts\OrderRepositoryInterface;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    public function listForUser(User $user, ?OrderStatus $status, int $perPage): CursorPaginator
    {
        return Order::query()
            ->with('items')
            ->where('user_id', $user->getKey())
            ->status($status)
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function createForUser(User $user, array $attributes): Order
    {
        /** @var Order $order */
        $order = parent::create([
            ...$attributes,
            'user_id' => $user->getKey(),
        ]);

        return $order;
    }

    public function updateOrder(Order $order, array $attributes): Order
    {
        /** @var Order $order */
        $order = parent::update($order, $attributes);

        return $order;
    }

    public function replaceItems(Order $order, array $items): void
    {
        $order->items()->delete();
        $order->items()->createMany($items);
    }

    public function withItems(Order $order): Order
    {
        return $order->load('items');
    }

    public function lock(Order $order): Order
    {
        /** @var Order $lockedOrder */
        $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

        return $lockedOrder;
    }

    public function hasPayments(Order $order): bool
    {
        return $order->payments()->exists();
    }

    public function deleteOrder(Order $order): bool
    {
        return parent::delete($order);
    }
}
