<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'description',
    'location',
    'qr_code',
    'image_url',
    'start_time',
    'end_time',
    'expired_at',
    'status',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_UPCOMING,
        self::STATUS_ACTIVE,
        self::STATUS_ENDED,
        self::STATUS_CANCELLED,
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(EventStaffAssignment::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(EventReward::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(HandoverRequest::class);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        // Guest/user portal: upcoming + active, chưa soft-delete.
        return $query->whereIn('status', [self::STATUS_UPCOMING, self::STATUS_ACTIVE]);
    }

    public function isUpcoming(): bool
    {
        return $this->status === self::STATUS_UPCOMING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isEnded(): bool
    {
        return $this->status === self::STATUS_ENDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_ENDED, self::STATUS_CANCELLED], true);
    }

    /**
     * QR chỉ hợp lệ khi status=active VÀ đang trong khung start_time..end_time.
     */
    public function isQrActive(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $now = now();

        return $now->greaterThanOrEqualTo($this->start_time)
            && $now->lessThanOrEqualTo($this->end_time);
    }

    public static function generateQrCode(): string
    {
        return 'EVT-'.Str::upper(Str::random(12));
    }
}
