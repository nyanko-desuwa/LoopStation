<?php

namespace App\Models;

use Database\Factories\UserWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'balance',
])]
class UserWallet extends Model
{
    /** @use HasFactory<UserWalletFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function earned(): HasMany
    {
        return $this->hasMany(PointEarned::class, 'wallet_id');
    }

    public function spent(): HasMany
    {
        return $this->hasMany(PointSpent::class, 'wallet_id');
    }
}
