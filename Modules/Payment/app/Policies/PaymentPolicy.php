<?php

namespace Modules\Payment\Policies;

use App\Models\User;
use Modules\Payment\Models\Payment;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $payment->order()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
