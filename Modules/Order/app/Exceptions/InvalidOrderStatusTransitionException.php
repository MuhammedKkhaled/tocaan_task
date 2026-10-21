<?php

namespace Modules\Order\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Order\Enums\OrderStatus;

class InvalidOrderStatusTransitionException extends BusinessRuleException
{
    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct(sprintf(
            'Cannot change order status from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
