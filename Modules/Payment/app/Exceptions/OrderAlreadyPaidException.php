<?php

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Symfony\Component\HttpFoundation\Response;

class OrderAlreadyPaidException extends BusinessRuleException
{
    protected int $status = Response::HTTP_CONFLICT;

    public function __construct()
    {
        parent::__construct('This order has already been paid.');
    }
}
