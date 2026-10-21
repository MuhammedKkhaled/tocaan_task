<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\GatewayResult;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Models\Payment;

interface PaymentGatewayInterface
{
    public function method(): PaymentMethod;

    /** @param array<string, mixed> $payload */
    public function charge(Payment $payment, array $payload = []): GatewayResult;
}
