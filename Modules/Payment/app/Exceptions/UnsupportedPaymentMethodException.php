<?php

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class UnsupportedPaymentMethodException extends BusinessRuleException
{
    public function __construct(string $method)
    {
        parent::__construct(sprintf("The payment method '%s' is not supported.", $method));
    }
}
