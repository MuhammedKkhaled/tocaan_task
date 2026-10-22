<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\GatewayResult;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

interface PaymentGatewayInterface
{
    public function gateway(): PaymentGateway;

    public function supports(PaymentMethod $method): bool;

    /** @param array<string, mixed> $payload */
    public function charge(Payment $payment, PaymentMethod $method, array $payload = []): GatewayResult;
}
