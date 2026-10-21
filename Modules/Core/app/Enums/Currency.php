<?php

namespace Modules\Core\Enums;

use Modules\Core\Enums\Concerns\HasValues;

enum Currency: string
{
    use HasValues;

    case USD = 'USD';
    case EUR = 'EUR';
    case EGP = 'EGP';
}
