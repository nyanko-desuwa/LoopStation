<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WasteType\StoreWasteTypeRequest;
use App\Http\Requests\WasteType\UpdateWasteTypeRequest;
use App\Http\Resources\WasteTypeResource;
use App\Models\WasteType;
use App\Services\WasteTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WasteTypeController extends Controller
{
    public function __construct(private readonly WasteTypeService $wasteTypeService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $viewer = $request->user();
        // waste_type.view ai cũng có; full list chỉ khi có quyền manage.
        $asManager = $viewer?->hasAnyPermission([
            'waste_type.update',
            'waste_type.delete',
            'waste_type.create',
        ]) ?? false;

        $wasteTypes = $this->wasteTypeService->list(
            filters: [
                'is_system' => $request->query('is_system'),
                'search' => $request->query('search'),
                'per_page' => $request->integer('per_page', 50),
            ],
            viewer: $viewer,
            asManager: $asManager,
        );

        return WasteTypeResource::collection($wasteTypes);
    }

    public function show(Request $request, WasteType $wasteType): WasteTypeResource
    {
        $viewer = $request->user();
        $asManager = $viewer?->hasAnyPermission([
            'waste_type.update',
            'waste_type.delete',
            'waste_type.create',
        ]) ?? false;

        // Loại custom của người khác thì user thường không xem được.
        if (! $this->wasteTypeService->isVisibleTo($viewer, $wasteType, $asManager)) {
            abort(404);
        }

        return new WasteTypeResource($wasteType);
    }

    public function store(StoreWasteTypeRequest $request): JsonResponse
    {
        $actor = $request->user();
        // Chỉ manager (waste_type.create) mới được tạo loại chuẩn is_system=true.
        $asSystem = $actor->hasPermission('waste_type.create')
            && (bool) $request->validated('is_system', false);

        $wasteType = $this->wasteTypeService->create($request->validated(), $actor, $asSystem);

        return response()->json([
            'message' => __('waste_types.messages.created'),
            'waste_type' => new WasteTypeResource($wasteType),
        ], 201);
    }

    public function update(UpdateWasteTypeRequest $request, WasteType $wasteType): JsonResponse
    {
        $wasteType = $this->wasteTypeService->update($wasteType, $request->validated());

        return response()->json([
            'message' => __('waste_types.messages.updated'),
            'waste_type' => new WasteTypeResource($wasteType),
        ]);
    }

    public function destroy(Request $request, WasteType $wasteType): JsonResponse
    {
        $actor = $request->user();
        $asManager = $actor?->hasPermission('waste_type.delete') ?? false;

        // User thường chỉ xóa được custom của chính mình (create_custom).
        if (! $asManager && ! ($actor?->hasPermission('waste_type.create_custom') ?? false)) {
            abort(403);
        }

        $this->wasteTypeService->delete($wasteType, $actor, $asManager);

        return response()->json([
            'message' => __('waste_types.messages.deleted'),
        ]);
    }
}
