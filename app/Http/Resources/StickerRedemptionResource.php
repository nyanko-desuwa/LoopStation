<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\StickerRedemption */
class StickerRedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sticker_id' => $this->sticker_id,
            'sticker' => new StickerResource($this->whenLoaded('sticker')),
            'quantity_used' => $this->quantity_used,
            'fulfillment_method' => $this->fulfillment_method,
            'status' => $this->status,
            'facility_id' => $this->facility_id,
            'staff_id' => $this->staff_id,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_note' => $this->shipping_note,
            'items' => StickerRedemptionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
