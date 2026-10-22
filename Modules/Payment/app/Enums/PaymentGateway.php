<?php

namespace Modules\Payment\Enums;

use Modules\Core\Enums\Concerns\HasValues;

enum PaymentGateway: string
{
    use HasValues;

    case Kashier = 'kashier';
    case Paymob = 'paymob';
    case MyFatoorah = 'myfatoorah';
}
