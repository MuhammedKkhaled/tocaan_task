<?php

namespace Modules\Order\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class OrderNotPendingException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Only pending orders can be updated.');
    }
}
