<?php

namespace Modules\Payment\Repositories;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment);
    }

    public function createForOrder(Order $order, array $attributes): Payment
    {
        /** @var Payment $payment */
        $payment = parent::create([
            ...$attributes,
            'order_id' => $order->getKey(),
        ]);

        return $payment;
    }

    public function updatePayment(Payment $payment, array $attributes): Payment
    {
        /** @var Payment $payment */
        $payment = parent::update($payment, $attributes);

        return $payment;
    }

    public function listForOrder(Order $order, int $perPage): CursorPaginator
    {
        return Payment::query()
            ->where('order_id', $order->getKey())
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function listForUser(User $user, ?PaymentStatus $status, int $perPage): CursorPaginator
    {
        return Payment::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->getKey()))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function successfulPaymentExists(Order $order): bool
    {
        return Payment::query()
            ->where('order_id', $order->getKey())
            ->where('status', PaymentStatus::Successful->value)
            ->exists();
    }
}
