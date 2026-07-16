<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\StickerRewardRule */
class StickerRewardRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sticker_id' => $this->sticker_id,
            'reward_item_id' => $this->reward_item_id,
            'reward_item' => new StickerRewardItemResource($this->whenLoaded('rewardItem')),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
