<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'registration_type' => EventRegistration::TYPE_VISIT,
            'status' => EventRegistration::STATUS_REGISTERED,
            'minigame_status' => EventRegistration::MINIGAME_NOT_ELIGIBLE,
            'checked_in_at' => null,
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn () => ['event_id' => $event->id]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function handover(): static
    {
        return $this->state(fn () => ['registration_type' => EventRegistration::TYPE_HANDOVER]);
    }

    public function attended(): static
    {
        return $this->state(fn () => [
            'status' => EventRegistration::STATUS_ATTENDED,
            'checked_in_at' => now(),
        ]);
    }
}
