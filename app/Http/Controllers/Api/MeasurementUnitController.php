<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeasurementUnit\StoreMeasurementUnitRequest;
use App\Http\Requests\MeasurementUnit\UpdateMeasurementUnitRequest;
use App\Http\Resources\MeasurementUnitResource;
use App\Models\MeasurementUnit;
use App\Services\MeasurementUnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeasurementUnitController extends Controller
{
    public function __construct(private readonly MeasurementUnitService $measurementUnitService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $units = $this->measurementUnitService->list([
            'category' => $request->query('category'),
            'is_system' => $request->query('is_system'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 50),
        ]);

        return MeasurementUnitResource::collection($units);
    }

    public function show(MeasurementUnit $measurementUnit): MeasurementUnitResource
    {
        return new MeasurementUnitResource($measurementUnit);
    }

    public function store(StoreMeasurementUnitRequest $request): JsonResponse
    {
        $unit = $this->measurementUnitService->create($request->validated(), $request->user());

        return response()->json([
            'message' => __('measurement_units.messages.created'),
            'measurement_unit' => new MeasurementUnitResource($unit),
        ], 201);
    }

    public function update(UpdateMeasurementUnitRequest $request, MeasurementUnit $measurementUnit): JsonResponse
    {
        $unit = $this->measurementUnitService->update($measurementUnit, $request->validated());

        return response()->json([
            'message' => __('measurement_units.messages.updated'),
            'measurement_unit' => new MeasurementUnitResource($unit),
        ]);
    }

    public function destroy(Request $request, MeasurementUnit $measurementUnit): JsonResponse
    {
        if (! $request->user()?->hasPermission('measurement_unit.delete')) {
            abort(403);
        }

        $this->measurementUnitService->delete($measurementUnit);

        return response()->json([
            'message' => __('measurement_units.messages.deleted'),
        ]);
    }
}
