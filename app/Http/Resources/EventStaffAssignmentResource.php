<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventStaffAssignment */
class EventStaffAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'staff_id' => $this->staff_id,
            'staff' => new UserResource($this->whenLoaded('staff')),
            'assigned_at' => $this->assigned_at,
        ];
    }
}
