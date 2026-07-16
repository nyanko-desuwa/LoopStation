<?php

namespace App\Models;

use Database\Factories\UserStickerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'sticker_id',
    'quantity',
    'total_obtained',
    'first_obtained_at',
    'last_obtained_at',
])]
class UserSticker extends Model
{
    /** @use HasFactory<UserStickerFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'sticker_id' => 'integer',
            'quantity' => 'integer',
            'total_obtained' => 'integer',
            'first_obtained_at' => 'datetime',
            'last_obtained_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class);
    }
}
