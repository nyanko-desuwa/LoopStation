<?php

namespace App\Models;

use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'user_id',
    'registration_type',
    'status',
    'minigame_status',
    'checked_in_at',
])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_VISIT = 'visit';

    public const TYPE_HANDOVER = 'handover';

    public const TYPE_WALKIN = 'walkin';

    public const TYPES = [
        self::TYPE_VISIT,
        self::TYPE_HANDOVER,
        self::TYPE_WALKIN,
    ];

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_ABSENT = 'absent';

    public const STATUSES = [
        self::STATUS_REGISTERED,
        self::STATUS_ATTENDED,
        self::STATUS_ABSENT,
    ];

    public const MINIGAME_NOT_ELIGIBLE = 'not_eligible';

    public const MINIGAME_UNLOCKED = 'unlocked';

    public const MINIGAME_PLAYED = 'played';

    public const MINIGAME_STATUSES = [
        self::MINIGAME_NOT_ELIGIBLE,
        self::MINIGAME_UNLOCKED,
        self::MINIGAME_PLAYED,
    ];

    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'user_id' => 'integer',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
