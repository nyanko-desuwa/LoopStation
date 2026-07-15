<?php

namespace App\Models;

use Database\Factories\WasteTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'icon',
    'is_system',
    'created_by',
])]
class WasteType extends Model
{
    /** @use HasFactory<WasteTypeFactory> */
    use HasFactory, SoftDeletes;

    public const UPDATED_AT = null;

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

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
