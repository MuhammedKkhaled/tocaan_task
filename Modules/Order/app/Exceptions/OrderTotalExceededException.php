<?php

namespace Modules\Order\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class OrderTotalExceededException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Order total exceeds the supported maximum.');
    }
}
