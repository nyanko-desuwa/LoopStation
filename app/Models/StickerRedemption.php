<?php

namespace App\Models;

use Database\Factories\StickerRedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'sticker_id',
    'quantity_used',
    'fulfillment_method',
    'status',
    'facility_id',
    'staff_id',
    'recipient_name',
    'recipient_phone',
    'shipping_address',
    'shipping_note',
])]
class StickerRedemption extends Model
{
    /** @use HasFactory<StickerRedemptionFactory> */
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
            'sticker_id' => 'integer',
            'quantity_used' => 'integer',
            'facility_id' => 'integer',
            'staff_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class, 'sticker_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StickerRedemptionItem::class, 'redemption_id');
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
