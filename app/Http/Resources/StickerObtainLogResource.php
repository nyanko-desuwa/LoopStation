<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\StickerObtainLog */
class StickerObtainLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sticker_id' => $this->sticker_id,
            'sticker' => new StickerResource($this->whenLoaded('sticker')),
            'source_content_id' => $this->source_content_id,
            'source_content' => $this->whenLoaded('sourceContent', fn () => $this->sourceContent ? [
                'id' => $this->sourceContent->id,
                'title' => $this->sourceContent->title,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
