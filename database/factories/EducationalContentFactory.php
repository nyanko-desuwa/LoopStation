<?php

namespace Database\Factories;

use App\Models\EducationalContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationalContent>
 */
class EducationalContentFactory extends Factory
{
    protected $model = EducationalContent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'author_id' => User::factory(),
            'approved_by_id' => null,
            'thumbnail_url' => null,
            'status' => EducationalContent::STATUS_PENDING,
            'timer_seconds' => 120,
            'points_reward' => fake()->numberBetween(5, 50),
            'sticker_set_id' => null,
        ];
    }

    public function published(?User $approver = null): static
    {
        return $this->state(fn () => [
            'status' => EducationalContent::STATUS_PUBLISHED,
            'approved_by_id' => $approver?->id ?? User::factory(),
        ]);
    }

    public function rejected(?User $approver = null): static
    {
        return $this->state(fn () => [
            'status' => EducationalContent::STATUS_REJECTED,
            'approved_by_id' => $approver?->id ?? User::factory(),
        ]);
    }

    public function byAuthor(User $author): static
    {
        return $this->state(fn () => ['author_id' => $author->id]);
    }
}
