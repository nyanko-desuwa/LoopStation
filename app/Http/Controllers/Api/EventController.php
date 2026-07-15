<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\AssignStaffEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\StoreEventRewardRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Event\UpdateEventRewardRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventRewardResource;
use App\Http\Resources\EventStaffAssignmentResource;
use App\Models\Event;
use App\Models\EventReward;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __construct(private readonly EventService $eventService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'event.update',
            'event.delete',
            'event.assign_staff',
            'event.manage_rewards',
        ]) ?? false;

        $events = $this->eventService->list([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 15),
        ], $asManager);

        return EventResource::collection($events);
    }

    public function show(Request $request, Event $event): EventResource
    {
        $asManager = $request->user()?->hasAnyPermission([
            'event.update',
            'event.delete',
            'event.assign_staff',
        ]) ?? false;

        // Guest/user không xem cancelled/ended trừ khi manager.
        if (! $asManager && ! in_array($event->status, [Event::STATUS_UPCOMING, Event::STATUS_ACTIVE], true)) {
            abort(404);
        }

        $event->load(['rewards', 'staffAssignments.staff']);

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->validated(), $request->user());

        return response()->json([
            'message' => __('events.messages.created'),
            'event' => new EventResource($event),
        ], 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event = $this->eventService->update($event, $request->validated());

        return response()->json([
            'message' => __('events.messages.updated'),
            'event' => new EventResource($event),
        ]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.delete')) {
            abort(403);
        }

        $this->eventService->delete($event);

        return response()->json([
            'message' => __('events.messages.deleted'),
        ]);
    }

    public function activate(Request $request, Event $event): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.publish')) {
            abort(403);
        }

        $event = $this->eventService->activate($event);

        return response()->json([
            'message' => __('events.messages.activated'),
            'event' => new EventResource($event),
        ]);
    }

    public function end(Request $request, Event $event): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.end')) {
            abort(403);
        }

        $event = $this->eventService->end($event);

        return response()->json([
            'message' => __('events.messages.ended'),
            'event' => new EventResource($event),
        ]);
    }

    public function cancel(Request $request, Event $event): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.update')) {
            abort(403);
        }

        $event = $this->eventService->cancel($event);

        return response()->json([
            'message' => __('events.messages.cancelled'),
            'event' => new EventResource($event),
        ]);
    }

    public function assignStaff(AssignStaffEventRequest $request, Event $event): JsonResponse
    {
        $assignment = $this->eventService->assignStaff(
            $event,
            $request->user(),
            (int) $request->validated('staff_id')
        );

        return response()->json([
            'message' => __('events.messages.staff_assigned'),
            'assignment' => new EventStaffAssignmentResource($assignment->load('staff')),
        ], 201);
    }

    public function unassignStaff(Request $request, Event $event, int $staffId): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.assign_staff')) {
            abort(403);
        }

        $this->eventService->unassignStaff($event, $staffId);

        return response()->json([
            'message' => __('events.messages.staff_unassigned'),
        ]);
    }

    public function storeReward(StoreEventRewardRequest $request, Event $event): JsonResponse
    {
        $reward = $this->eventService->addReward($event, $request->validated());

        return response()->json([
            'message' => __('events.messages.reward_created'),
            'reward' => new EventRewardResource($reward),
        ], 201);
    }

    public function updateReward(UpdateEventRewardRequest $request, Event $event, EventReward $reward): JsonResponse
    {
        if ((int) $reward->event_id !== (int) $event->id) {
            abort(404);
        }

        $reward = $this->eventService->updateReward($reward, $request->validated());

        return response()->json([
            'message' => __('events.messages.reward_updated'),
            'reward' => new EventRewardResource($reward),
        ]);
    }

    public function destroyReward(Request $request, Event $event, EventReward $reward): JsonResponse
    {
        if (! $request->user()?->hasPermission('event.manage_rewards')) {
            abort(403);
        }

        if ((int) $reward->event_id !== (int) $event->id) {
            abort(404);
        }

        $this->eventService->deleteReward($reward);

        return response()->json([
            'message' => __('events.messages.reward_deleted'),
        ]);
    }
}
