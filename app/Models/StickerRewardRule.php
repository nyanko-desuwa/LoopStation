<?php

namespace App\Models;

use Database\Factories\StickerRewardRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sticker_id',
    'reward_item_id',
    'quantity',
    'status',
])]
class StickerRewardRule extends Model
{
    /** @use HasFactory<StickerRewardRuleFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    protected function casts(): array
    {
        return [
            'sticker_id' => 'integer',
            'reward_item_id' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class, 'sticker_id');
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(StickerRewardItem::class, 'reward_item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
