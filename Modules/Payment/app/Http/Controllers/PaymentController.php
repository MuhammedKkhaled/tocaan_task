<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\ListOrderPaymentsRequest;
use Modules\Payment\Http\Requests\ListPaymentsRequest;
use Modules\Payment\Http\Requests\ProcessPaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {
    }

    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $validated = $request->validated();
        $payment = $this->payments->process(
            $order,
            PaymentMethod::from($validated['method']),
            $validated['payload'] ?? [],
        );

        if ($payment->status === PaymentStatus::Failed) {
            return $this->successResponse(
                'The payment was declined: '.$payment->failure_reason,
                ['data' => new PaymentResource($payment)],
                Response::HTTP_PAYMENT_REQUIRED,
            );
        }

        return $this->successResponse(
            'Payment processed successfully.',
            ['data' => new PaymentResource($payment)],
            Response::HTTP_CREATED,
        );
    }

    public function forOrder(ListOrderPaymentsRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $payments = $this->payments->listForOrder($order, $request->validated());

        return $this->fromResource(PaymentResource::collection($payments))
            ->addMessageToResponse('Payments retrieved successfully.')
            ->toResponse();
    }

    public function index(ListPaymentsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $payments = $this->payments->listForUser($request->user(), $request->validated());

        return $this->fromResource(PaymentResource::collection($payments))
            ->addMessageToResponse('Payments retrieved successfully.')
            ->toResponse();
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return $this->successResponse(
            'Payment retrieved successfully.',
            ['data' => new PaymentResource($payment)],
        );
    }
}
