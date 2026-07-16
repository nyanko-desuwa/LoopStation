<?php

namespace App\Models;

use Database\Factories\PointSpentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'wallet_id',
    'points',
    'source_type',
    'reference_id',
    'description',
])]
class PointSpent extends Model
{
    /** @use HasFactory<PointSpentFactory> */
    use HasFactory;

    protected $table = 'point_spent';

    public const UPDATED_AT = null;

    public const SOURCE_REDEMPTION = 'redemption';

    public const SOURCE_MANAGER_ADJUST = 'manager_adjust';

    public const SOURCES = [
        self::SOURCE_REDEMPTION,
        self::SOURCE_MANAGER_ADJUST,
    ];

    protected function casts(): array
    {
        return [
            'wallet_id' => 'integer',
            'points' => 'integer',
            'reference_id' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }
}
