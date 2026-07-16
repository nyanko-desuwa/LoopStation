<?php

namespace Database\Factories;

use App\Models\StickerRewardItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerRewardItem>
 */
class StickerRewardItemFactory extends Factory
{
    protected $model = StickerRewardItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'image_url' => null,
            'description' => fake()->optional()->sentence(),
            'stock' => fake()->numberBetween(5, 50),
            'status' => StickerRewardItem::STATUS_ACTIVE,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => StickerRewardItem::STATUS_LOCKED]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
