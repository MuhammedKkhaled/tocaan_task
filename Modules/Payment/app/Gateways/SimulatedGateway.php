<?php

namespace Modules\Payment\Gateways;

use Illuminate\Support\Str;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\GatewayResult;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

abstract class SimulatedGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function supports(PaymentMethod $method): bool
    {
        return in_array($method->value, $this->config['methods'] ?? [], true);
    }

    public function charge(Payment $payment, PaymentMethod $method, array $payload = []): GatewayResult
    {
        $successful = ($payload['simulate_outcome'] ?? 'success') !== 'declined';
        $gateway = $this->gateway()->value;
        $raw = [
            'gateway' => $gateway,
            'method' => $method->value,
            'approved' => $successful,
            'simulated' => true,
        ];

        if (! $successful) {
            return new GatewayResult(
                successful: false,
                failureReason: "The simulated {$gateway} payment was declined.",
                raw: $raw,
            );
        }

        return new GatewayResult(
            successful: true,
            transactionId: $gateway.'_'.Str::uuid(),
            raw: $raw,
        );
    }
}
