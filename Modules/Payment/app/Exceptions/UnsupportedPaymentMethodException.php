<?php

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;

class UnsupportedPaymentMethodException extends BusinessRuleException
{
    public function __construct(PaymentGateway $gateway, PaymentMethod $method)
    {
        parent::__construct(sprintf(
            "The payment method '%s' is not supported by gateway '%s'.",
            $method->value,
            $gateway->value,
        ));
    }
}
