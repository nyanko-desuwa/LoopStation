<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserSticker */
class UserStickerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sticker_id' => $this->sticker_id,
            'sticker' => new StickerResource($this->whenLoaded('sticker')),
            'quantity' => $this->quantity,
            'total_obtained' => $this->total_obtained,
            'first_obtained_at' => $this->first_obtained_at,
            'last_obtained_at' => $this->last_obtained_at,
        ];
    }
}
