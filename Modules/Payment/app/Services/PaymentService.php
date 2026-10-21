<?php

namespace Modules\Payment\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Contracts\OrderRepositoryInterface;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Exceptions\OrderAlreadyPaidException;
use Modules\Payment\Exceptions\OrderNotPayableException;
use Modules\Payment\Exceptions\PaymentProcessingInProgressException;
use Modules\Payment\Models\Payment;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentGatewayManager $gateways,
        private readonly ReferenceGenerator $references,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function process(Order $order, PaymentMethod $method, array $payload = []): Payment
    {
        try {
            return Cache::lock("order-payment:{$order->getKey()}", 10)->block(
                5,
                fn (): Payment => DB::transaction(function () use ($order, $method, $payload): Payment {
                    $order = $this->orders->lock($order);

                    if ($order->status !== OrderStatus::Confirmed) {
                        throw new OrderNotPayableException();
                    }

                    if ($this->payments->successfulPaymentExists($order)) {
                        throw new OrderAlreadyPaidException();
                    }

                    $payment = $this->payments->createForOrder($order, [
                        'reference' => $this->references->paymentReference(),
                        'method' => $method,
                        'status' => PaymentStatus::Pending,
                        'amount' => $order->total_amount,
                        'currency' => $order->currency,
                    ]);

                    $result = $this->gateways->resolve($method)->charge($payment, $payload);

                    return $this->payments->updatePayment($payment, [
                        'status' => $result->successful
                            ? PaymentStatus::Successful
                            : PaymentStatus::Failed,
                        'gateway_transaction_id' => $result->transactionId,
                        'gateway_response' => $result->raw,
                        'failure_reason' => $result->failureReason,
                        'processed_at' => now(),
                    ]);
                }),
            );
        } catch (LockTimeoutException) {
            throw new PaymentProcessingInProgressException();
        }
    }

    /**
     * @param  array{per_page?: int, cursor?: string}  $filters
     * @return CursorPaginator<int, Payment>
     */
    public function listForOrder(Order $order, array $filters): CursorPaginator
    {
        return $this->payments->listForOrder($order, $filters['per_page'] ?? 15);
    }

    /**
     * @param  array{status?: string, per_page?: int, cursor?: string}  $filters
     * @return CursorPaginator<int, Payment>
     */
    public function listForUser(User $user, array $filters): CursorPaginator
    {
        $status = isset($filters['status'])
            ? PaymentStatus::from($filters['status'])
            : null;

        return $this->payments->listForUser($user, $status, $filters['per_page'] ?? 15);
    }

}
