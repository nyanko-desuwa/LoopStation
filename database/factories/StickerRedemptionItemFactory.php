<?php

namespace Database\Factories;

use App\Models\StickerRedemption;
use App\Models\StickerRedemptionItem;
use App\Models\StickerRewardItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerRedemptionItem>
 */
class StickerRedemptionItemFactory extends Factory
{
    protected $model = StickerRedemptionItem::class;

    public function definition(): array
    {
        return [
            'redemption_id' => StickerRedemption::factory(),
            'reward_item_id' => StickerRewardItem::factory(),
            'item_name' => fake()->words(2, true),
            'item_image_url' => null,
            'quantity' => 1,
        ];
    }
}
