<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sticker */
class StickerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'set_id' => $this->set_id,
            'set' => $this->whenLoaded('set', fn () => $this->set ? [
                'id' => $this->set->id,
                'name' => $this->set->name,
                'status' => $this->set->status,
            ] : null),
            'name' => $this->name,
            'image_url' => $this->image_url,
            'rarity' => $this->rarity,
            'drop_weight' => $this->drop_weight,
            'redeem_quantity_required' => $this->redeem_quantity_required,
            'bonus_points' => $this->bonus_points,
            'unlocks_content_id' => $this->unlocks_content_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
