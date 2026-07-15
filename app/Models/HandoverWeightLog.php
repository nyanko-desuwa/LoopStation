<?php

namespace App\Models;

use Database\Factories\HandoverWeightLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_id',
    'weight',
    'unit_id',
    'recorded_by',
    'notes',
])]
class HandoverWeightLog extends Model
{
    /** @use HasFactory<HandoverWeightLogFactory> */
    use HasFactory;

    // Append-only: chỉ ghi thêm, không có updated_at.
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'request_id' => 'integer',
            'weight' => 'decimal:2',
            'unit_id' => 'integer',
            'recorded_by' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(HandoverRequest::class, 'request_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unit_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
