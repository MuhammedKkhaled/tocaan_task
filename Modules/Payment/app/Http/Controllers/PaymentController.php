<?php

namespace Modules\Payment\Http\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Core\Http\Documentation\ApiErrorResponse;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\ListOrderPaymentsRequest;
use Modules\Payment\Http\Requests\ListPaymentsRequest;
use Modules\Payment\Http\Requests\ProcessPaymentRequest;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;

#[Group('Payments')]
class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {
    }

    /** Process a simulated payment for a confirmed order. */
    #[ApiResponse(status: 201, description: 'Payment processed', type: 'array{message: string, data: PaymentResource}', examples: [['message' => 'Payment processed successfully.', 'data' => []]])]
    #[ApiResponse(status: 402, description: 'Payment declined', type: 'array{message: string, data: PaymentResource}', examples: [['message' => 'The payment was declined.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(409)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $validated = $request->validated();
        $payment = $this->payments->process(
            $order,
            PaymentGateway::from($validated['gateway']),
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

    /** List payments for one order. */
    #[ApiResponse(description: 'Payments retrieved', type: 'array{message: string, data: list<PaymentResource>, meta: array<string, mixed>}', examples: [['message' => 'Payments retrieved successfully.', 'data' => [], 'meta' => ['items_Per_Page' => 15, 'Items_in_page' => 0, 'next_cursor' => null, 'previous_cursor' => null, 'next_pageUrl' => null, 'previous_pageUrl' => null]]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function forOrder(ListOrderPaymentsRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $payments = $this->payments->listForOrder($order, $request->validated());

        return $this->fromResource(PaymentResource::collection($payments))
            ->addMessageToResponse('Payments retrieved successfully.')
            ->toResponse();
    }

    /** List the authenticated user's payments. */
    #[ApiResponse(description: 'Payments retrieved', type: 'array{message: string, data: list<PaymentResource>, meta: array<string, mixed>}', examples: [['message' => 'Payments retrieved successfully.', 'data' => [], 'meta' => ['items_Per_Page' => 15, 'Items_in_page' => 0, 'next_cursor' => null, 'previous_cursor' => null, 'next_pageUrl' => null, 'previous_pageUrl' => null]]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function index(ListPaymentsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $payments = $this->payments->listForUser($request->user(), $request->validated());

        return $this->fromResource(PaymentResource::collection($payments))
            ->addMessageToResponse('Payments retrieved successfully.')
            ->toResponse();
    }

    /** Retrieve one payment. */
    #[ApiResponse(description: 'Payment retrieved', type: 'array{message: string, data: PaymentResource}', examples: [['message' => 'Payment retrieved successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(403)]
    #[ApiErrorResponse(404)]
    #[ApiErrorResponse(429)]
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return $this->successResponse(
            'Payment retrieved successfully.',
            ['data' => new PaymentResource($payment)],
        );
    }
}
