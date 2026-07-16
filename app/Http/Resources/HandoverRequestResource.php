<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HandoverRequest */
class HandoverRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'facility_id' => $this->facility_id,
            'facility' => $this->whenLoaded('facility', fn () => new FacilityResource($this->facility)),
            'staff_id' => $this->staff_id,
            'staff' => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'role' => $this->staff->role,
            ] : null),
            'event_id' => $this->event_id,
            'classification_type' => $this->classification_type,
            'estimated_weight' => $this->estimated_weight,
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? new MeasurementUnitResource($this->unit) : null),
            'appointment_time' => $this->appointment_time,
            'expired_at' => $this->expired_at,
            'reschedule_count' => $this->reschedule_count,
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'cancel_reason' => $this->cancel_reason,
            'notes' => $this->notes,
            'items' => HandoverWasteItemResource::collection($this->whenLoaded('wasteItems')),
            'weight_logs' => HandoverWeightLogResource::collection($this->whenLoaded('weightLogs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
