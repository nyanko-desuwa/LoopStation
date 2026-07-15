<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // QR code chỉ lộ cho người có quyền quản lý event.
        $canManage = $request->user()?->hasAnyPermission([
            'event.update',
            'event.assign_staff',
            'event.manage_rewards',
        ]) ?? false;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'qr_code' => $this->when($canManage, $this->qr_code),
            'image_url' => $this->image_url,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'expired_at' => $this->expired_at,
            'status' => $this->status,
            'rewards' => EventRewardResource::collection($this->whenLoaded('rewards')),
            'staff_assignments' => EventStaffAssignmentResource::collection($this->whenLoaded('staffAssignments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
