<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;
use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialsException extends BusinessRuleException
{
    protected int $status = Response::HTTP_UNAUTHORIZED;

    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}
