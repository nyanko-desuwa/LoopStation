<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventRegistration */
class EventRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'registration_type' => $this->registration_type,
            'status' => $this->status,
            'minigame_status' => $this->minigame_status,
            'checked_in_at' => $this->checked_in_at,
            'created_at' => $this->created_at,
        ];
    }
}
