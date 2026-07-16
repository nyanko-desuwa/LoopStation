<?php

namespace App\Models;

use Database\Factories\StickerRedemptionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'redemption_id',
    'reward_item_id',
    'item_name',
    'item_image_url',
    'quantity',
])]
class StickerRedemptionItem extends Model
{
    /** @use HasFactory<StickerRedemptionItemFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'redemption_id' => 'integer',
            'reward_item_id' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(StickerRedemption::class, 'redemption_id');
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(StickerRewardItem::class, 'reward_item_id');
    }
}
