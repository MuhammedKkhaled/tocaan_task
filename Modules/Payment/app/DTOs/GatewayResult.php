<?php

namespace Modules\Payment\DTOs;

final readonly class GatewayResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $successful,
        public ?string $transactionId = null,
        public ?string $failureReason = null,
        public array $raw = [],
    ) {
    }
}
