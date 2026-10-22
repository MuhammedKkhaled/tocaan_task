<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Enums\PaymentGateway;

final class MyFatoorahGateway extends SimulatedGateway
{
    public function gateway(): PaymentGateway
    {
        return PaymentGateway::MyFatoorah;
    }
}
