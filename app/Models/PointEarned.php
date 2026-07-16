<?php

namespace App\Models;

use Database\Factories\PointEarnedFactory;
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
class PointEarned extends Model
{
    /** @use HasFactory<PointEarnedFactory> */
    use HasFactory;

    protected $table = 'point_earned';

    public const UPDATED_AT = null;

    public const SOURCE_HANDOVER = 'handover';

    public const SOURCE_EVENT_MINIGAME = 'event_minigame';

    public const SOURCE_CONTENT_READ = 'content_read';

    public const SOURCE_MANAGER_ADJUST = 'manager_adjust';

    public const SOURCE_REDEMPTION_REFUND = 'redemption_refund';

    public const SOURCE_STICKER_BONUS = 'sticker_bonus';

    public const SOURCES = [
        self::SOURCE_HANDOVER,
        self::SOURCE_EVENT_MINIGAME,
        self::SOURCE_CONTENT_READ,
        self::SOURCE_MANAGER_ADJUST,
        self::SOURCE_REDEMPTION_REFUND,
        self::SOURCE_STICKER_BONUS,
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
