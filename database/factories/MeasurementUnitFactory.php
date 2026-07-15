<?php

namespace Database\Factories;

use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementUnit>
 */
class MeasurementUnitFactory extends Factory
{
    protected $model = MeasurementUnit::class;

    public function definition(): array
    {
        $category = fake()->randomElement(MeasurementUnit::CATEGORIES);

        return [
            'name' => fake()->unique()->words(2, true),
            'symbol' => fake()->unique()->lexify('??'),
            'category' => $category,
            // Mặc định unit custom (manager thêm); state system() cho seed.
            'is_system' => false,
            'created_by' => null,
        ];
    }

    // Đơn vị gốc hệ thống - không cho xóa qua API.
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'created_by' => null,
        ]);
    }

    public function weight(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => MeasurementUnit::CATEGORY_WEIGHT,
        ]);
    }

    public function volume(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => MeasurementUnit::CATEGORY_VOLUME,
        ]);
    }

    // Không đặt tên count() - trùng Factory::count().
    public function asCount(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => MeasurementUnit::CATEGORY_COUNT,
        ]);
    }
}
