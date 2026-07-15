<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventStaffAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventStaffAssignment>
 */
class EventStaffAssignmentFactory extends Factory
{
    protected $model = EventStaffAssignment::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'staff_id' => User::factory()->staff(),
            'assigned_at' => now(),
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn () => ['event_id' => $event->id]);
    }

    public function forStaff(User $staff): static
    {
        return $this->state(fn () => ['staff_id' => $staff->id]);
    }
}
