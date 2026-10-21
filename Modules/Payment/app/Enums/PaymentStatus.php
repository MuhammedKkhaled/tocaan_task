<?php

namespace Modules\Payment\Enums;

use Modules\Core\Enums\Concerns\HasValues;

enum PaymentStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
}
