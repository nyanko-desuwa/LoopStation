<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Station',
            'type' => fake()->randomElement(Facility::TYPES),
            'address' => fake()->address(),
            'latitude' => fake()->optional()->latitude(8, 23),
            'longitude' => fake()->optional()->longitude(102, 110),
            'image_url' => null,
            'status' => Facility::STATUS_ACTIVE,
        ];
    }

    public function station(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Facility::TYPE_STATION,
        ]);
    }

    public function office(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Facility::TYPE_OFFICE,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Facility::STATUS_LOCKED,
        ]);
    }
}
