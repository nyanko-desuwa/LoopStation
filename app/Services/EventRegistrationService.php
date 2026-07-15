<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class EventRegistrationService
{
    /**
     * @param  array{status?: string|null, per_page?: int}  $filters
     */
    public function listForEvent(Event $event, array $filters = []): LengthAwarePaginator
    {
        $query = EventRegistration::query()
            ->where('event_id', $event->id)
            ->with('user')
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 200));

        return $query->paginate($perPage);
    }

    /**
     * User tự đăng ký sự kiện (visit hoặc handover).
     *
     * @param  array{registration_type?: string}  $data
     */
    public function register(Event $event, User $user, array $data = []): EventRegistration
    {
        $this->assertEventOpenForRegistration($event);

        if (EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_registered'),
            ]);
        }

        $type = $data['registration_type'] ?? EventRegistration::TYPE_VISIT;
        if (! in_array($type, [EventRegistration::TYPE_VISIT, EventRegistration::TYPE_HANDOVER], true)) {
            // walkin chỉ qua QR flow.
            throw ValidationException::withMessages([
                'registration_type' => __('events.messages.invalid_registration_type'),
            ]);
        }

        return EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'registration_type' => $type,
            'status' => EventRegistration::STATUS_REGISTERED,
            'minigame_status' => EventRegistration::MINIGAME_NOT_ELIGIBLE,
        ]);
    }

    /**
     * Check-in bằng QR (user đã đăng ký) hoặc tạo walkin registration.
     */
    public function checkIn(Event $event, User $user): EventRegistration
    {
        if (! $event->isQrActive()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.qr_inactive'),
            ]);
        }

        $registration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($registration === null) {
            // Walk-in: tạo registration type walkin + check-in ngay.
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'registration_type' => EventRegistration::TYPE_WALKIN,
                'status' => EventRegistration::STATUS_ATTENDED,
                'minigame_status' => EventRegistration::MINIGAME_NOT_ELIGIBLE,
                'checked_in_at' => now(),
            ]);

            return $registration;
        }

        if ($registration->isCheckedIn()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_checked_in'),
            ]);
        }

        $registration->update([
            'status' => EventRegistration::STATUS_ATTENDED,
            'checked_in_at' => now(),
        ]);

        return $registration->refresh();
    }

    /**
     * Staff đánh dấu vắng mặt.
     */
    public function markAbsent(EventRegistration $registration): EventRegistration
    {
        if ($registration->status === EventRegistration::STATUS_ATTENDED) {
            throw ValidationException::withMessages([
                'registration' => __('events.messages.already_attended'),
            ]);
        }

        $registration->update([
            'status' => EventRegistration::STATUS_ABSENT,
        ]);

        return $registration->refresh();
    }

    /**
     * Staff check-in giúp user (không cần QR active nếu event active).
     */
    public function staffCheckIn(EventRegistration $registration): EventRegistration
    {
        if ($registration->isCheckedIn()) {
            throw ValidationException::withMessages([
                'registration' => __('events.messages.already_checked_in'),
            ]);
        }

        $registration->update([
            'status' => EventRegistration::STATUS_ATTENDED,
            'checked_in_at' => now(),
        ]);

        return $registration->refresh();
    }

    /**
     * Mở minigame sau khi user hoàn thành điều kiện (vd nộp rác).
     */
    public function unlockMinigame(EventRegistration $registration): EventRegistration
    {
        if ($registration->minigame_status !== EventRegistration::MINIGAME_NOT_ELIGIBLE) {
            throw ValidationException::withMessages([
                'registration' => __('events.messages.minigame_already_unlocked'),
            ]);
        }

        $registration->update([
            'minigame_status' => EventRegistration::MINIGAME_UNLOCKED,
        ]);

        return $registration->refresh();
    }

    public function cancelOwn(EventRegistration $registration, User $user): void
    {
        if ($registration->user_id !== $user->id) {
            abort(403);
        }

        if ($registration->isCheckedIn()) {
            throw ValidationException::withMessages([
                'registration' => __('events.messages.cannot_cancel_checked_in'),
            ]);
        }

        $registration->delete();
    }

    private function assertEventOpenForRegistration(Event $event): void
    {
        if ($event->isTerminal()) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.already_closed'),
            ]);
        }

        if ($event->expired_at && now()->greaterThan($event->expired_at)) {
            throw ValidationException::withMessages([
                'event' => __('events.messages.registration_expired'),
            ]);
        }
    }
}
