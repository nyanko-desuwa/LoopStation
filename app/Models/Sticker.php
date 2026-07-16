<?php

namespace App\Models;

use Database\Factories\StickerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'set_id',
    'name',
    'image_url',
    'rarity',
    'drop_weight',
    'redeem_quantity_required',
    'bonus_points',
    'unlocks_content_id',
    'status',
])]
class Sticker extends Model
{
    /** @use HasFactory<StickerFactory> */
    use HasFactory, SoftDeletes;

    public const RARITY_COMMON = 'common';

    public const RARITY_RARE = 'rare';

    public const RARITY_SPECIAL = 'special';

    public const RARITIES = [
        self::RARITY_COMMON,
        self::RARITY_RARE,
        self::RARITY_SPECIAL,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    protected function casts(): array
    {
        return [
            'set_id' => 'integer',
            'drop_weight' => 'integer',
            'redeem_quantity_required' => 'integer',
            'bonus_points' => 'integer',
            'unlocks_content_id' => 'integer',
        ];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(StickerSet::class, 'set_id');
    }

    public function unlocksContent(): BelongsTo
    {
        return $this->belongsTo(EducationalContent::class, 'unlocks_content_id');
    }

    public function userStickers(): HasMany
    {
        return $this->hasMany(UserSticker::class, 'sticker_id');
    }

    public function obtainLogs(): HasMany
    {
        return $this->hasMany(StickerObtainLog::class, 'sticker_id');
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
