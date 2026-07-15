<?php

namespace Database\Factories;

use App\Models\HandoverRequest;
use App\Models\HandoverWeightLog;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandoverWeightLog>
 */
class HandoverWeightLogFactory extends Factory
{
    protected $model = HandoverWeightLog::class;

    public function definition(): array
    {
        return [
            'request_id' => HandoverRequest::factory()->approved(),
            'weight' => fake()->randomFloat(2, 0.5, 50),
            'unit_id' => MeasurementUnit::factory()->system()->weight(),
            'recorded_by' => User::factory()->staff(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forRequest(HandoverRequest $request): static
    {
        return $this->state(fn () => ['request_id' => $request->id]);
    }

    public function by(User $staff): static
    {
        return $this->state(fn () => ['recorded_by' => $staff->id]);
    }
}
