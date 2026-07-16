<?php

namespace Database\Factories;

use App\Models\RewardCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RewardCatalog>
 */
class RewardCatalogFactory extends Factory
{
    protected $model = RewardCatalog::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'image_url' => null,
            'points_cost' => fake()->numberBetween(10, 200),
            'stock' => fake()->numberBetween(5, 100),
            'status' => RewardCatalog::STATUS_ACTIVE,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => RewardCatalog::STATUS_LOCKED]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
