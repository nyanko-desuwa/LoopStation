<?php

namespace Database\Factories;

use App\Models\Sticker;
use App\Models\StickerRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerRedemption>
 */
class StickerRedemptionFactory extends Factory
{
    protected $model = StickerRedemption::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sticker_id' => Sticker::factory(),
            'quantity_used' => 1,
            'fulfillment_method' => StickerRedemption::METHOD_PICKUP,
            'status' => StickerRedemption::STATUS_PENDING,
            'facility_id' => null,
            'staff_id' => null,
            'recipient_name' => null,
            'recipient_phone' => null,
            'shipping_address' => null,
            'shipping_note' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function delivery(): static
    {
        return $this->state(fn () => [
            'fulfillment_method' => StickerRedemption::METHOD_DELIVERY,
            'recipient_name' => fake()->name(),
            'recipient_phone' => '09'.fake()->numerify('########'),
            'shipping_address' => fake()->address(),
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn () => ['status' => StickerRedemption::STATUS_FULFILLED]);
    }
}
