<?php

namespace Modules\Core\Exceptions;

use DomainException;
use Symfony\Component\HttpFoundation\Response;

abstract class BusinessRuleException extends DomainException
{
    protected int $status = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function status(): int
    {
        return $this->status;
    }
}
