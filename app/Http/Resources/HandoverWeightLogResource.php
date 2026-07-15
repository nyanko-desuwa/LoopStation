<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HandoverWeightLog */
class HandoverWeightLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weight' => $this->weight,
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn () => new MeasurementUnitResource($this->unit)),
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
                'role' => $this->recorder->role,
            ]),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
