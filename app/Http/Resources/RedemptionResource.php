<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Redemption */
class RedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'reward_id' => $this->reward_id,
            'reward' => new RewardCatalogResource($this->whenLoaded('reward')),
            'points_spent' => $this->points_spent,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'fulfillment_method' => $this->fulfillment_method,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_note' => $this->shipping_note,
            'transaction_id' => $this->transaction_id,
            'fulfilled_by_id' => $this->fulfilled_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
