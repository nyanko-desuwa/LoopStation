<?php

namespace Database\Factories;

use App\Models\HandoverRequest;
use App\Models\HandoverWasteItem;
use App\Models\MeasurementUnit;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandoverWasteItem>
 */
class HandoverWasteItemFactory extends Factory
{
    protected $model = HandoverWasteItem::class;

    public function definition(): array
    {
        return [
            'request_id' => HandoverRequest::factory(),
            'waste_type_id' => WasteType::factory()->system(),
            'weight' => fake()->randomFloat(2, 0.5, 30),
            'unit_id' => MeasurementUnit::factory()->system()->weight(),
        ];
    }

    public function forRequest(HandoverRequest $request): static
    {
        return $this->state(fn () => ['request_id' => $request->id]);
    }
}
