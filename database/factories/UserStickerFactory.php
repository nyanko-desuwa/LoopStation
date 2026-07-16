<?php

namespace Database\Factories;

use App\Models\Sticker;
use App\Models\User;
use App\Models\UserSticker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSticker>
 */
class UserStickerFactory extends Factory
{
    protected $model = UserSticker::class;

    public function definition(): array
    {
        $now = now();

        return [
            'user_id' => User::factory(),
            'sticker_id' => Sticker::factory(),
            'quantity' => 1,
            'total_obtained' => 1,
            'first_obtained_at' => $now,
            'last_obtained_at' => $now,
        ];
    }
}
