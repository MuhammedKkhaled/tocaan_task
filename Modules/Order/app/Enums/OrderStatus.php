<?php

namespace Modules\Order\Enums;

use Modules\Core\Enums\Concerns\HasValues;

enum OrderStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => $next === self::Cancelled,
            self::Cancelled => false,
        };
    }
}
