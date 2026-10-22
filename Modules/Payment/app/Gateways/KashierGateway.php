<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Enums\PaymentGateway;

final class KashierGateway extends SimulatedGateway
{
    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Kashier;
    }
}
