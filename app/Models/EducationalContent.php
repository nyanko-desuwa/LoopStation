<?php

namespace App\Models;

use Database\Factories\EducationalContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'content',
    'author_id',
    'approved_by_id',
    'thumbnail_url',
    'status',
    'timer_seconds',
    'points_reward',
    'sticker_set_id',
])]
class EducationalContent extends Model
{
    /** @use HasFactory<EducationalContentFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PUBLISHED,
        self::STATUS_REJECTED,
    ];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'approved_by_id' => 'integer',
            'timer_seconds' => 'integer',
            'points_reward' => 'integer',
            'sticker_set_id' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ContentRead::class, 'content_id');
    }

    public function stickerSet(): BelongsTo
    {
        return $this->belongsTo(StickerSet::class, 'sticker_set_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
