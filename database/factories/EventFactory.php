<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 14))->setTime(8, 0);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->address(),
            'qr_code' => Event::generateQrCode(),
            'image_url' => null,
            'start_time' => $start,
            'end_time' => (clone $start)->addHours(4),
            'expired_at' => null,
            'status' => Event::STATUS_UPCOMING,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_UPCOMING]);
    }

    public function active(): static
    {
        return $this->state(function () {
            $start = now()->subHour();

            return [
                'status' => Event::STATUS_ACTIVE,
                'start_time' => $start,
                'end_time' => now()->addHours(3),
            ];
        });
    }

    public function ended(): static
    {
        return $this->state(function () {
            $end = now()->subDay();

            return [
                'status' => Event::STATUS_ENDED,
                'start_time' => (clone $end)->subHours(4),
                'end_time' => $end,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_CANCELLED]);
    }
}
