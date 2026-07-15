<?php

namespace App\Models;

use Database\Factories\MeasurementUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'symbol',
    'category',
    'is_system',
    'created_by',
])]
class MeasurementUnit extends Model
{
    /** @use HasFactory<MeasurementUnitFactory> */
    use HasFactory, SoftDeletes;

    public const UPDATED_AT = null;

    public const CATEGORY_WEIGHT = 'weight';

    public const CATEGORY_VOLUME = 'volume';

    public const CATEGORY_COUNT = 'count';

    public const CATEGORIES = [
        self::CATEGORY_WEIGHT,
        self::CATEGORY_VOLUME,
        self::CATEGORY_COUNT,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
