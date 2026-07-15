<?php

namespace App\Models;

use Database\Factories\RewardCatalogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'description',
    'image_url',
    'points_cost',
    'stock',
    'status',
])]
class RewardCatalog extends Model
{
    /** @use HasFactory<RewardCatalogFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'reward_catalog';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class, 'reward_id');
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

    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }
}
