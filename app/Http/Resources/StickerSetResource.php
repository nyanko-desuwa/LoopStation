<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\StickerSet */
class StickerSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'theme' => $this->theme,
            'cover_image_url' => $this->cover_image_url,
            'status' => $this->status,
            'stickers_count' => $this->whenCounted('stickers'),
            'stickers' => StickerResource::collection($this->whenLoaded('stickers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
