<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Str;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\GatewayResult;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

final class PaypalGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::Paypal;
    }

    public function charge(Payment $payment, array $payload = []): GatewayResult
    {
        if (($payload['paypal_order_id'] ?? null) === 'paypal_fail') {
            return new GatewayResult(
                successful: false,
                failureReason: 'PayPal declined the payment.',
                raw: $this->response(false),
            );
        }

        return new GatewayResult(
            successful: true,
            transactionId: 'paypal_'.Str::uuid(),
            raw: $this->response(true),
        );
    }

    /** @return array{gateway: string, approved: bool, sandbox: bool} */
    private function response(bool $approved): array
    {
        return [
            'gateway' => $this->method()->value,
            'approved' => $approved,
            'sandbox' => (bool) ($this->config['sandbox'] ?? true),
        ];
    }
}
