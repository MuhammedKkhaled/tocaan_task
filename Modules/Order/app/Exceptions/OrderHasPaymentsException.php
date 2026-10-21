<?php

namespace Modules\Order\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Symfony\Component\HttpFoundation\Response;

class OrderHasPaymentsException extends BusinessRuleException
{
    protected int $status = Response::HTTP_CONFLICT;

    public function __construct()
    {
        parent::__construct('Cannot delete an order that has payments.');
    }
}
