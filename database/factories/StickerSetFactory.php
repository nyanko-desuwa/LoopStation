<?php

namespace Database\Factories;

use App\Models\StickerSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerSet>
 */
class StickerSetFactory extends Factory
{
    protected $model = StickerSet::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'theme' => fake()->optional()->word(),
            'cover_image_url' => null,
            'status' => StickerSet::STATUS_ACTIVE,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => StickerSet::STATUS_LOCKED]);
    }
}
