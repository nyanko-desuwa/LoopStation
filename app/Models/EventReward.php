<?php

namespace App\Models;

use Database\Factories\EventRewardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'name',
    'description',
    'quantity',
    'remaining',
])]
class EventReward extends Model
{
    /** @use HasFactory<EventRewardFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'quantity' => 'integer',
            'remaining' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function hasStock(): bool
    {
        return $this->remaining > 0;
    }
}
