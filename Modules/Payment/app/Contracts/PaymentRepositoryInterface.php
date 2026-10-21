<?php

namespace Modules\Payment\Contracts;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Modules\Core\Contracts\BaseRepositoryInterface;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function createForOrder(Order $order, array $attributes): Payment;

    /** @param array<string, mixed> $attributes */
    public function updatePayment(Payment $payment, array $attributes): Payment;

    /** @return CursorPaginator<int, Payment> */
    public function listForOrder(Order $order, int $perPage): CursorPaginator;

    /** @return CursorPaginator<int, Payment> */
    public function listForUser(User $user, ?PaymentStatus $status, int $perPage): CursorPaginator;

    public function successfulPaymentExists(Order $order): bool;
}
