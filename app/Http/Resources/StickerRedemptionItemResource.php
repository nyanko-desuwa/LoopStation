<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\StickerRedemptionItem */
class StickerRedemptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'redemption_id' => $this->redemption_id,
            'reward_item_id' => $this->reward_item_id,
            'item_name' => $this->item_name,
            'item_image_url' => $this->item_image_url,
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
        ];
    }
}
