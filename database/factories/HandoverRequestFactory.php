<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\HandoverRequest;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandoverRequest>
 */
class HandoverRequestFactory extends Factory
{
    protected $model = HandoverRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'facility_id' => Facility::factory(),
            'staff_id' => null,
            'event_id' => null,
            'classification_type' => fake()->optional()->randomElement(HandoverRequest::CLASSIFICATIONS),
            'estimated_weight' => fake()->optional()->randomFloat(2, 1, 50),
            'unit_id' => null,
            'appointment_time' => fake()->optional()->dateTimeBetween('+1 day', '+7 days'),
            'expired_at' => null,
            'reschedule_count' => 0,
            'status' => HandoverRequest::STATUS_PENDING,
            'reject_reason' => null,
            'cancel_reason' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function atFacility(Facility $facility): static
    {
        return $this->state(fn () => ['facility_id' => $facility->id]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => HandoverRequest::STATUS_PENDING]);
    }

    public function approved(?User $staff = null): static
    {
        return $this->state(fn () => [
            'status' => HandoverRequest::STATUS_APPROVED,
            'staff_id' => $staff?->id,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => HandoverRequest::STATUS_COMPLETED]);
    }

    public function cancelled(string $reason = HandoverRequest::CANCEL_USER): static
    {
        return $this->state(fn () => [
            'status' => HandoverRequest::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ]);
    }

    public function rejected(string $reason = 'Không đạt yêu cầu'): static
    {
        return $this->state(fn () => [
            'status' => HandoverRequest::STATUS_REJECTED,
            'reject_reason' => $reason,
        ]);
    }

    public function withUnit(?MeasurementUnit $unit = null): static
    {
        return $this->state(function () use ($unit) {
            $unitId = $unit?->id ?? MeasurementUnit::factory()->system()->create()->id;

            return [
                'unit_id' => $unitId,
                'estimated_weight' => fake()->randomFloat(2, 1, 50),
            ];
        });
    }
}
