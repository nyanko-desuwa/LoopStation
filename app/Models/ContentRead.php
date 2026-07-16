<?php

namespace App\Models;

use Database\Factories\ContentReadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'content_id',
    'started_at',
    'completed_at',
    'rewarded',
    'read_date',
])]
class ContentRead extends Model
{
    /** @use HasFactory<ContentReadFactory> */
    use HasFactory;

    // Chỉ started_at/completed_at (no updated_at chuẩn Laravel timestamps).
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'content_id' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rewarded' => 'boolean',
            'read_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(EducationalContent::class, 'content_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
