<?php

namespace App\Models;

use Database\Factories\StickerSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'theme',
    'cover_image_url',
    'status',
])]
class StickerSet extends Model
{
    /** @use HasFactory<StickerSetFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    public function stickers(): HasMany
    {
        return $this->hasMany(Sticker::class, 'set_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(EducationalContent::class, 'sticker_set_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}
