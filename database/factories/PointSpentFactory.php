<?php

namespace Database\Factories;

use App\Models\PointSpent;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointSpent>
 */
class PointSpentFactory extends Factory
{
    protected $model = PointSpent::class;

    public function definition(): array
    {
        return [
            'wallet_id' => UserWallet::factory(),
            'points' => fake()->numberBetween(1, 50),
            'source_type' => PointSpent::SOURCE_MANAGER_ADJUST,
            'reference_id' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function forWallet(UserWallet $wallet): static
    {
        return $this->state(fn () => ['wallet_id' => $wallet->id]);
    }
}
