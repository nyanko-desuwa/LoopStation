<?php

namespace App\Models;

use Database\Factories\RedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'reward_id',
    'points_spent',
    'quantity',
    'status',
    'fulfillment_method',
    'recipient_name',
    'recipient_phone',
    'shipping_address',
    'shipping_note',
    'transaction_id',
    'fulfilled_by_id',
])]
class Redemption extends Model
{
    /** @use HasFactory<RedemptionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SHIPPING = 'shipping';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SHIPPING,
        self::STATUS_FULFILLED,
        self::STATUS_CANCELLED,
    ];

    public const METHOD_PICKUP = 'pickup';

    public const METHOD_DELIVERY = 'delivery';

    public const METHODS = [
        self::METHOD_PICKUP,
        self::METHOD_DELIVERY,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'reward_id' => 'integer',
            'points_spent' => 'integer',
            'quantity' => 'integer',
            'transaction_id' => 'integer',
            'fulfilled_by_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(RewardCatalog::class, 'reward_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PointSpent::class, 'transaction_id');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SHIPPING], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_FULFILLED, self::STATUS_CANCELLED], true);
    }
}
