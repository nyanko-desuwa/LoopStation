<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Handover\AssignStaffHandoverRequest;
use App\Http\Requests\Handover\RecordWeightHandoverRequest;
use App\Http\Requests\Handover\RejectHandoverRequest;
use App\Http\Requests\Handover\RescheduleHandoverRequest;
use App\Http\Requests\Handover\StoreHandoverRequest;
use App\Http\Requests\Handover\UpdateHandoverRequest;
use App\Http\Resources\HandoverRequestResource;
use App\Http\Resources\HandoverWeightLogResource;
use App\Models\HandoverRequest;
use App\Services\HandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HandoverController extends Controller
{
    public function __construct(private readonly HandoverService $handoverService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $handovers = $this->handoverService->list($request->user(), [
            'status' => $request->query('status'),
            'facility_id' => $request->query('facility_id'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return HandoverRequestResource::collection($handovers);
    }

    public function show(Request $request, HandoverRequest $handover): HandoverRequestResource
    {
        $handover = $this->handoverService->findVisible($handover, $request->user());

        return new HandoverRequestResource($handover);
    }

    public function store(StoreHandoverRequest $request): JsonResponse
    {
        $handover = $this->handoverService->create($request->user(), $request->validated());

        return response()->json([
            'message' => __('handovers.messages.created'),
            'handover' => new HandoverRequestResource($handover),
        ], 201);
    }

    public function update(UpdateHandoverRequest $request, HandoverRequest $handover): JsonResponse
    {
        $handover = $this->handoverService->update($handover, $request->user(), $request->validated());

        return response()->json([
            'message' => __('handovers.messages.updated'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function cancel(Request $request, HandoverRequest $handover): JsonResponse
    {
        $handover = $this->handoverService->cancel($handover, $request->user());

        return response()->json([
            'message' => __('handovers.messages.cancelled'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function approve(Request $request, HandoverRequest $handover): JsonResponse
    {
        if (! $request->user()?->hasPermission('handover.approve')) {
            abort(403);
        }

        $staffId = $request->integer('staff_id') ?: null;
        $handover = $this->handoverService->approve($handover, $request->user(), $staffId);

        return response()->json([
            'message' => __('handovers.messages.approved'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function reject(RejectHandoverRequest $request, HandoverRequest $handover): JsonResponse
    {
        $handover = $this->handoverService->reject(
            $handover,
            $request->user(),
            $request->validated('reject_reason')
        );

        return response()->json([
            'message' => __('handovers.messages.rejected'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function assignStaff(AssignStaffHandoverRequest $request, HandoverRequest $handover): JsonResponse
    {
        $handover = $this->handoverService->assignStaff(
            $handover,
            $request->user(),
            (int) $request->validated('staff_id')
        );

        return response()->json([
            'message' => __('handovers.messages.staff_assigned'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function reschedule(RescheduleHandoverRequest $request, HandoverRequest $handover): JsonResponse
    {
        $handover = $this->handoverService->reschedule(
            $handover,
            $request->user(),
            $request->validated('appointment_time')
        );

        return response()->json([
            'message' => __('handovers.messages.rescheduled'),
            'handover' => new HandoverRequestResource($handover),
        ]);
    }

    public function recordWeight(RecordWeightHandoverRequest $request, HandoverRequest $handover): JsonResponse
    {
        $log = $this->handoverService->recordWeight($handover, $request->user(), $request->validated());

        return response()->json([
            'message' => __('handovers.messages.weight_recorded'),
            'weight_log' => new HandoverWeightLogResource($log->load(['unit', 'recorder'])),
        ], 201);
    }

    public function complete(Request $request, HandoverRequest $handover): JsonResponse
    {
        if (! $request->user()?->hasPermission('handover.complete')) {
            abort(403);
        }

        $result = $this->handoverService->complete($handover, $request->user());

        return response()->json([
            'message' => __('handovers.messages.completed'),
            'handover' => new HandoverRequestResource($result['handover']),
            'points_awarded' => $result['points_awarded'],
        ]);
    }
}
