<?php

namespace App\Models;

use Database\Factories\StickerObtainLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'sticker_id',
    'source_content_id',
])]
class StickerObtainLog extends Model
{
    /** @use HasFactory<StickerObtainLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'sticker_id' => 'integer',
            'source_content_id' => 'integer',
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

    public function sourceContent(): BelongsTo
    {
        return $this->belongsTo(EducationalContent::class, 'source_content_id');
    }
}
