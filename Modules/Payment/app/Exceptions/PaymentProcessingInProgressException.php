<?php

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Symfony\Component\HttpFoundation\Response;

class PaymentProcessingInProgressException extends BusinessRuleException
{
    protected int $status = Response::HTTP_CONFLICT;

    public function __construct()
    {
        parent::__construct('A payment for this order is already being processed.');
    }
}
