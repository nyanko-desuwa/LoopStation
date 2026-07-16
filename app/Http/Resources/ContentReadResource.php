<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ContentRead */
class ContentReadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'content_id' => $this->content_id,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'rewarded' => $this->rewarded,
            'read_date' => $this->read_date?->toDateString(),
        ];
    }
}
