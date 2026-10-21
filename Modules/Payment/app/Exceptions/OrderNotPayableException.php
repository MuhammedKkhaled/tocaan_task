<?php

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class OrderNotPayableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Order must be confirmed before payment.');
    }
}
