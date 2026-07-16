<?php

namespace Database\Factories;

use App\Models\Sticker;
use App\Models\StickerSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sticker>
 */
class StickerFactory extends Factory
{
    protected $model = Sticker::class;

    public function definition(): array
    {
        return [
            'set_id' => StickerSet::factory(),
            'name' => fake()->words(2, true),
            'image_url' => '/storage/stickers/'.fake()->uuid().'.png',
            'rarity' => Sticker::RARITY_COMMON,
            'drop_weight' => fake()->numberBetween(1, 10),
            'redeem_quantity_required' => 1,
            'bonus_points' => 0,
            'unlocks_content_id' => null,
            'status' => Sticker::STATUS_ACTIVE,
        ];
    }

    public function rare(int $bonusPoints = 10): static
    {
        return $this->state(fn () => [
            'rarity' => Sticker::RARITY_RARE,
            'bonus_points' => $bonusPoints,
            'drop_weight' => 2,
        ]);
    }

    public function special(int $bonusPoints = 50): static
    {
        return $this->state(fn () => [
            'rarity' => Sticker::RARITY_SPECIAL,
            'bonus_points' => $bonusPoints,
            'drop_weight' => 1,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => Sticker::STATUS_LOCKED]);
    }

    public function inSet(StickerSet $set): static
    {
        return $this->state(fn () => ['set_id' => $set->id]);
    }
}
