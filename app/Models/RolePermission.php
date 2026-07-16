<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'role',
    'permission_id',
    'created_by',
])]
class RolePermission extends Model
{
    public const UPDATED_AT = null;

    public const ROLES = ['user', 'staff', 'manager'];

    protected function casts(): array
    {
        return [
            'permission_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}