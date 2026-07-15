<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MeasurementUnit */
class MeasurementUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'category' => $this->category,
            'is_system' => $this->is_system,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
