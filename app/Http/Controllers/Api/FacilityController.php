<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\StoreFacilityRequest;
use App\Http\Requests\Facility\UpdateFacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FacilityController extends Controller
{
    public function __construct(private readonly FacilityService $facilityService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->role === 'manager';

        $facilities = $this->facilityService->list(
            filters: [
                'status' => $request->query('status'),
                'type' => $request->query('type'),
                'per_page' => $request->integer('per_page', 15),
            ],
            asManager: $asManager,
        );

        return FacilityResource::collection($facilities);
    }

    public function show(Facility $facility): FacilityResource
    {
        // User thường không xem cơ sở locked; manager xem được tất cả.
        if ($facility->isLocked() && request()->user()?->role !== 'manager') {
            abort(404);
        }

        return new FacilityResource($facility);
    }

    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $facility = $this->facilityService->create($request->validated());

        return response()->json([
            'message' => __('facilities.messages.created'),
            'facility' => new FacilityResource($facility),
        ], 201);
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $facility = $this->facilityService->update($facility, $request->validated());

        return response()->json([
            'message' => __('facilities.messages.updated'),
            'facility' => new FacilityResource($facility),
        ]);
    }

    public function destroy(Request $request, Facility $facility): JsonResponse
    {
        if ($request->user()?->role !== 'manager') {
            abort(403);
        }

        $this->facilityService->delete($facility);

        return response()->json([
            'message' => __('facilities.messages.deleted'),
        ]);
    }
}
