<?php

namespace App\Models;

use Database\Factories\HandoverWasteItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_id',
    'waste_type_id',
    'weight',
    'unit_id',
])]
class HandoverWasteItem extends Model
{
    /** @use HasFactory<HandoverWasteItemFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'request_id' => 'integer',
            'waste_type_id' => 'integer',
            'weight' => 'decimal:2',
            'unit_id' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(HandoverRequest::class, 'request_id');
    }

    public function wasteType(): BelongsTo
    {
        return $this->belongsTo(WasteType::class, 'waste_type_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }
}
