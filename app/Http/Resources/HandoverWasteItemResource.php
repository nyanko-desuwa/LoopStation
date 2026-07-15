<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HandoverWasteItem */
class HandoverWasteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waste_type_id' => $this->waste_type_id,
            'waste_type' => $this->whenLoaded('wasteType', fn () => new WasteTypeResource($this->wasteType)),
            'weight' => $this->weight,
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn () => new MeasurementUnitResource($this->unit)),
            'created_at' => $this->created_at,
        ];
    }
}
