<?php

namespace Database\Factories;

use App\Models\Sticker;
use App\Models\StickerObtainLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StickerObtainLog>
 */
class StickerObtainLogFactory extends Factory
{
    protected $model = StickerObtainLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sticker_id' => Sticker::factory(),
            'source_content_id' => null,
        ];
    }
}
