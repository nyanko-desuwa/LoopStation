<?php

namespace Database\Factories;

use App\Models\PointEarned;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointEarned>
 */
class PointEarnedFactory extends Factory
{
    protected $model = PointEarned::class;

    public function definition(): array
    {
        return [
            'wallet_id' => UserWallet::factory(),
            'points' => fake()->numberBetween(1, 100),
            'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
            'reference_id' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function forWallet(UserWallet $wallet): static
    {
        return $this->state(fn () => ['wallet_id' => $wallet->id]);
    }

    public function fromHandover(int $handoverId): static
    {
        return $this->state(fn () => [
            'source_type' => PointEarned::SOURCE_HANDOVER,
            'reference_id' => $handoverId,
        ]);
    }
}
