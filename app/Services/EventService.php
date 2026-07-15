<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventReward;
use App\Models\EventStaffAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class EventService
{
    /**
     * @param  array{status?: string|null, search?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = Event::query()->latest('start_time');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            // Guest/user: only upcoming + active.
            $query->publicVisible();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    /**
     * @param  array{
     *   title: string,
     *   description?: string|null,
     *   location: string,
     *   image_url?: string|null,
     *   start_time: string,
     *   end_time: string,
     *   expired_at?: string|null,
     *   status?: string,
     *   qr_code?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): Event
    {
        $this->assertTimeRange($data['start_time'], $data['end_time']);

        return Event::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'qr_code' => $data['qr_code'] ?? Event::generateQrCode(),
            'image_url' => $data['image_url'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'expired_at' => $data['expired_at'] ?? null,
            'status' => $data['status'] ?? Event::STATUS_UPCOMING,
        ]);
    }

    /**
     * @param  array{
     *   title?: string,
     *   description?: string|null,
     *   location?: string,
     *   image_url?: string|null,
     *   start_time?: string,
     *   end_time?: string,
     *   expired_at?: string|null,
     *   status?: string
     * }  $data
     */
    public function update(Event $event, array $data): Event
    {
        if ($event->isTerminal() && isset($data['status']) && $data['status'] !== $event->status) {
            // Cho phép re-open? Không - terminal chỉ soft-delete / xem.
        }

        $start = $data['start_time'] ?? $event->start_time;
        $end = $data['end_time'] ?? $event->end_time;
        $this->assertTimeRange($start, $end);

        $event->fill(collect($data)->only([
            'title',
            'description',
            'location',
            'image_url',
            'start_time',
            'end_time',
            'expired_at',
            'status',
        ])->all());
        $event->save();

        return $event->refresh();
    }

    public function activate(Event $event): Event
    {
        if ($event->isTerminal()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_closed'),
            ]);
        }

        $event->update(['status' => Event::STATUS_ACTIVE]);

        return $event->refresh();
    }

    public function end(Event $event): Event
    {
        if ($event->isCancelled()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_closed'),
            ]);
        }

        $event->update(['status' => Event::STATUS_ENDED]);

        return $event->refresh();
    }

    public function cancel(Event $event): Event
    {
        if ($event->isEnded()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_ended'),
            ]);
        }

        $event->update(['status' => Event::STATUS_CANCELLED]);

        return $event->refresh();
    }

    public function delete(Event $event): void
    {
        // Soft-delete. Không chặn nếu còn registration - giữ audit.
        $event->delete();
    }

    /**
     * Phân công staff. Chặn: không phải staff/manager, khác facility actor, overlap thời gian.
     */
    public function assignStaff(Event $event, User $actor, int $staffId): EventStaffAssignment
    {
        if ($event->isTerminal()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_closed'),
            ]);
        }

        $staff = User::query()->find($staffId);

        if ($staff === null || ! in_array($staff->role, ['staff', 'manager'], true)) {
            throw ValidationException::withMessages([
                'staff_id' => __('events.messages.invalid_staff'),
            ]);
        }

        // Chỉ staff cùng cơ sở với manager/actor.
        if ($actor->facility_id && (int) $staff->facility_id !== (int) $actor->facility_id) {
            throw ValidationException::withMessages([
                'staff_id' => __('events.messages.staff_facility_mismatch'),
            ]);
        }

        if (EventStaffAssignment::query()
            ->where('event_id', $event->id)
            ->where('staff_id', $staffId)
            ->exists()) {
            throw ValidationException::withMessages([
                'staff_id' => __('events.messages.staff_already_assigned'),
            ]);
        }

        $this->assertNoTimeOverlap($staffId, $event);

        return EventStaffAssignment::create([
            'event_id' => $event->id,
            'staff_id' => $staffId,
            'assigned_at' => now(),
        ]);
    }

    public function unassignStaff(Event $event, int $staffId): void
    {
        $deleted = EventStaffAssignment::query()
            ->where('event_id', $event->id)
            ->where('staff_id', $staffId)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'staff_id' => __('events.messages.staff_not_assigned'),
            ]);
        }
    }

    /**
     * @param  array{name: string, description?: string|null, quantity: int}  $data
     */
    public function addReward(Event $event, array $data): EventReward
    {
        if ($event->isTerminal()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_closed'),
            ]);
        }

        $quantity = (int) $data['quantity'];

        return EventReward::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'quantity' => $quantity,
            'remaining' => $quantity,
        ]);
    }

    /**
     * @param  array{name?: string, description?: string|null, quantity?: int, remaining?: int}  $data
     */
    public function updateReward(EventReward $reward, array $data): EventReward
    {
        if (isset($data['quantity'])) {
            $quantity = (int) $data['quantity'];
            $data['quantity'] = $quantity;
            // Nếu remaining không gửi, giữ min(remaining, quantity).
            if (! array_key_exists('remaining', $data)) {
                $data['remaining'] = min($reward->remaining, $quantity);
            }
        }

        if (array_key_exists('remaining', $data)) {
            $remaining = (int) $data['remaining'];
            $max = (int) ($data['quantity'] ?? $reward->quantity);
            if ($remaining > $max) {
                throw ValidationException::withMessages([
                    'remaining' => __('events.messages.remaining_exceeds_quantity'),
                ]);
            }
            $data['remaining'] = $remaining;
        }

        $reward->fill(collect($data)->only(['name', 'description', 'quantity', 'remaining'])->all());
        $reward->save();

        return $reward->refresh();
    }

    public function deleteReward(EventReward $reward): void
    {
        $reward->delete();
    }

    /**
     * Tra QR: chỉ trả event khi QR active trong khung giờ.
     */
    public function findByQr(string $qrCode): Event
    {
        $event = Event::query()->where('qr_code', $qrCode)->first();

        if ($event === null || ! $event->isQrActive()) {
            throw ValidationException::withMessages([
                'qr_code' => __('events.messages.qr_inactive'),
            ]);
        }

        return $event;
    }

    private function assertTimeRange(mixed $start, mixed $end): void
    {
        $startAt = now()->parse($start);
        $endAt = now()->parse($end);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages([
                'end_time' => __('events.messages.invalid_time_range'),
            ]);
        }
    }

    /**
     * Chặn staff đã gán event khác overlap start_time..end_time (thay trigger DB).
     */
    private function assertNoTimeOverlap(int $staffId, Event $event): void
    {
        $overlap = EventStaffAssignment::query()
            ->where('staff_id', $staffId)
            ->whereHas('event', function ($q) use ($event): void {
                $q->whereNull('deleted_at')
                    ->where('id', '!=', $event->id)
                    ->where('start_time', '<', $event->end_time)
                    ->where('end_time', '>', $event->start_time)
                    ->whereNotIn('status', [Event::STATUS_CANCELLED, Event::STATUS_ENDED]);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'staff_id' => __('events.messages.staff_time_overlap'),
            ]);
        }
    }
}
