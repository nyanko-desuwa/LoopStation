<?php

namespace Database\Factories;

use App\Models\Sticker;
use App\Models\StickerRewardItem;
use App\Models\StickerRewardRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerRewardRule>
 */
class StickerRewardRuleFactory extends Factory
{
    protected $model = StickerRewardRule::class;

    public function definition(): array
    {
        return [
            'sticker_id' => Sticker::factory(),
            'reward_item_id' => StickerRewardItem::factory(),
            'quantity' => 1,
            'status' => StickerRewardRule::STATUS_ACTIVE,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => StickerRewardRule::STATUS_LOCKED]);
    }
}
