<?php

namespace App\Models;

use Database\Factories\HandoverRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'facility_id',
    'staff_id',
    'event_id',
    'classification_type',
    'estimated_weight',
    'unit_id',
    'appointment_time',
    'expired_at',
    'reschedule_count',
    'status',
    'reject_reason',
    'cancel_reason',
    'notes',
])]
class HandoverRequest extends Model
{
    /** @use HasFactory<HandoverRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    public const CLASSIFICATION_CLEANED_FLATTENED = 'cleaned_flattened';

    public const CLASSIFICATION_CLEANED = 'cleaned';

    public const CLASSIFICATION_AS_IS = 'as_is';

    public const CLASSIFICATION_MIXED = 'mixed';

    public const CLASSIFICATIONS = [
        self::CLASSIFICATION_CLEANED_FLATTENED,
        self::CLASSIFICATION_CLEANED,
        self::CLASSIFICATION_AS_IS,
        self::CLASSIFICATION_MIXED,
    ];

    public const CANCEL_USER = 'user_cancel';

    public const CANCEL_STAFF = 'staff_cancel';

    public const CANCEL_AUTO_EXPIRE = 'auto_expire';

    public const CANCEL_RESCHEDULE_EXCEEDED = 'reschedule_exceeded';

    public const CANCEL_REASONS = [
        self::CANCEL_USER,
        self::CANCEL_STAFF,
        self::CANCEL_AUTO_EXPIRE,
        self::CANCEL_RESCHEDULE_EXCEEDED,
    ];

    public const MAX_RESCHEDULES = 2;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'facility_id' => 'integer',
            'staff_id' => 'integer',
            'event_id' => 'integer',
            'estimated_weight' => 'decimal:2',
            'unit_id' => 'integer',
            'appointment_time' => 'datetime',
            'expired_at' => 'datetime',
            'reschedule_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }

    public function wasteItems(): HasMany
    {
        return $this->hasMany(HandoverWasteItem::class, 'request_id');
    }

    public function weightLogs(): HasMany
    {
        return $this->hasMany(HandoverWeightLog::class, 'request_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForFacility(Builder $query, int $facilityId): Builder
    {
        return $query->where('facility_id', $facilityId);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ], true);
    }
}
