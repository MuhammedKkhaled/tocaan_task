<?php

namespace Modules\Core\Http\Documentation;

use Attribute;
use Dedoc\Scramble\Attributes\Response;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class ApiErrorResponse extends Response
{
    public function __construct(int $status)
    {
        [$description, $message, $error] = match ($status) {
            401 => ['Unauthenticated', 'Unauthenticated. Please log in.', null],
            403 => ['Forbidden', 'You are not allowed to perform this action.', null],
            404 => ['Not found', 'The requested resource was not found.', null],
            409 => ['Conflict', 'The request conflicts with the current resource state.', null],
            422 => ['Validation failed', 'The given data was invalid.', ['field' => ['The field is required.']]],
            429 => ['Too many requests', 'Too many requests. Please slow down.', null],
        };

        parent::__construct(
            status: $status,
            description: $description,
            type: 'array{message: string, error: mixed}',
            examples: [[
                'message' => $message,
                'error' => $error,
            ]],
        );
    }
}
