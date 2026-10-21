<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Enums\Currency;
use Modules\Order\Database\Factories\OrderFactory;
use Modules\Order\Enums\OrderStatus;
use Modules\Payment\Models\Payment;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'number',
        'status',
        'total_amount',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
            'currency' => Currency::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeStatus(Builder $query, ?OrderStatus $status): Builder
    {
        return $query->when(
            $status,
            fn (Builder $query): Builder => $query->where('status', $status->value),
        );
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
