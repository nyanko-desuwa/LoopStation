<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'resource',
    'action',
    'name',
    'description',
    'is_system',
])]
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function scopeForResource(Builder $query, string $resource): Builder
    {
        return $query->where('resource', $resource);
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
