<?php

namespace Modules\Core\Support;

use Illuminate\Support\Str;

final class ReferenceGenerator
{
    public function orderNumber(): string
    {
        return sprintf(
            'ORD-%s-%s',
            now()->format('Ymd'),
            Str::upper(Str::random(4)),
        );
    }

    public function paymentReference(): string
    {
        return Str::uuid()->toString();
    }
}
