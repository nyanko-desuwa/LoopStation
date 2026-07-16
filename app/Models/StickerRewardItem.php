<?php

namespace App\Models;

use Database\Factories\StickerRewardItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'image_url',
    'description',
    'stock',
    'status',
])]
class StickerRewardItem extends Model
{
    /** @use HasFactory<StickerRewardItemFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(StickerRewardRule::class, 'reward_item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }
}
