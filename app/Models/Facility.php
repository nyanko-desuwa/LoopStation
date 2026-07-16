<?php

namespace App\Models;

use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'type',
    'address',
    'latitude',
    'longitude',
    'image_url',
    'status',
])]
class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_STATION = 'station';

    public const TYPE_OFFICE = 'office';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_LOCKED = 'locked';

    public const TYPES = [self::TYPE_STATION, self::TYPE_OFFICE];

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_LOCKED];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
