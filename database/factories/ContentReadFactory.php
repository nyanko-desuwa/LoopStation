<?php

namespace Database\Factories;

use App\Models\ContentRead;
use App\Models\EducationalContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRead>
 */
class ContentReadFactory extends Factory
{
    protected $model = ContentRead::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content_id' => EducationalContent::factory(),
            'started_at' => now(),
            'completed_at' => null,
            'rewarded' => false,
            'read_date' => now()->toDateString(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
        ]);
    }

    public function rewarded(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
            'rewarded' => true,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn () => ['read_date' => $date]);
    }
}
