<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Enums\Currency;
use Modules\Order\Models\Order;
use Modules\Payment\Database\Factories\PaymentFactory;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'reference',
        'method',
        'status',
        'amount',
        'currency',
        'gateway_transaction_id',
        'gateway_response',
        'failure_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'currency' => Currency::class,
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
