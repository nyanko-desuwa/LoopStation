<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventReward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReward>
 */
class EventRewardFactory extends Factory
{
    protected $model = EventReward::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);

        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'quantity' => $quantity,
            'remaining' => $quantity,
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn () => ['event_id' => $event->id]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['remaining' => 0]);
    }
}
