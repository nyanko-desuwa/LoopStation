<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteType>
 */
class WasteTypeFactory extends Factory
{
    protected $model = WasteType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'icon' => fake()->optional()->randomElement(['♻️', '📄', '🧴', '🍃', '🔩']),
            // Mặc định loại chuẩn; state custom() cho user tự thêm.
            'is_system' => true,
            'created_by' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'created_by' => null,
        ]);
    }

    // Loại rác custom của 1 user - chỉ họ thấy trong list public.
    public function custom(User|int|null $creator = null): static
    {
        return $this->state(function (array $attributes) use ($creator) {
            $creatorId = $creator instanceof User
                ? $creator->id
                : ($creator ?? User::factory()->create()->id);

            return [
                'is_system' => false,
                'created_by' => $creatorId,
            ];
        });
    }
}
