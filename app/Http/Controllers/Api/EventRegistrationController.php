<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\RegisterEventRequest;
use App\Http\Resources\EventRegistrationResource;
use App\Http\Resources\EventRewardResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\EventRegistrationService;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventRegistrationController extends Controller
{
    public function __construct(
        private readonly EventRegistrationService $registrationService,
        private readonly EventService $eventService,
    ) {
    }

    public function index(Request $request, Event $event): AnonymousResourceCollection
    {
        if (! $request->user()?->hasPermission('event_registration.view')) {
            abort(403);
        }

        $regs = $this->registrationService->listForEvent($event, [
            'status' => $request->query('status'),
            'per_page' => $request->integer('per_page', 50),
        ]);

        return EventRegistrationResource::collection($regs);
    }

    public function store(RegisterEventRequest $request, Event $event): JsonResponse
    {
        $registration = $this->registrationService->register(
            $event,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => __('events.messages.registered'),
            'registration' => new EventRegistrationResource($registration),
        ], 201);
    }

    public function destroy(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $this->registrationService->cancelOwn($registration, $request->user());

        return response()->json([
            'message' => __('events.messages.registration_cancelled'),
        ]);
    }

    /**
     * POST /api/events/check-in { qr_code }
     */
    public function checkInByQr(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string', 'max:100'],
        ]);

        $event = $this->eventService->findByQr($request->string('qr_code')->toString());
        $registration = $this->registrationService->checkIn($event, $request->user());

        return response()->json([
            'message' => __('events.messages.checked_in'),
            'registration' => new EventRegistrationResource($registration->load('user')),
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status,
            ],
        ]);
    }

    public function staffCheckIn(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->hasPermission('event_registration.check_in')) {
            abort(403);
        }

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $registration = $this->registrationService->staffCheckIn($registration);

        return response()->json([
            'message' => __('events.messages.checked_in'),
            'registration' => new EventRegistrationResource($registration),
        ]);
    }

    public function markAbsent(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->hasPermission('event_registration.mark_absent')) {
            abort(403);
        }

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $registration = $this->registrationService->markAbsent($registration);

        return response()->json([
            'message' => __('events.messages.marked_absent'),
            'registration' => new EventRegistrationResource($registration),
        ]);
    }

    public function unlockMinigame(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.unlock_minigame')) {
            abort(403);
        }

        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $registration = $this->registrationService->unlockMinigame($registration);

        return response()->json([
            'message' => __('events.messages.minigame_unlocked'),
            'registration' => new EventRegistrationResource($registration),
        ]);
    }

    /**
     * User chơi minigame đã unlock → cộng điểm + random quà vật lý (event_minigame).
     */
    public function playMinigame(Request $request, Event $event, EventRegistration $registration): JsonResponse
    {
        if ((int) $registration->event_id !== (int) $event->id) {
            abort(404);
        }

        $result = $this->registrationService->playMinigame($registration, $request->user());

        return response()->json([
            'message' => $result['already_played']
                ? __('events.messages.minigame_already_played')
                : __('events.messages.minigame_played'),
            'registration' => new EventRegistrationResource($result['registration']),
            'points_awarded' => $result['points_awarded'],
            'reward' => $result['reward'] ? new EventRewardResource($result['reward']) : null,
            'already_played' => $result['already_played'],
        ]);
    }
}
