<?php

namespace Modules\Payment\Enums;

use Modules\Core\Enums\Concerns\HasValues;

enum PaymentMethod: string
{
    use HasValues;

    case CreditCard = 'credit_card';
    case Paypal = 'paypal';
}
