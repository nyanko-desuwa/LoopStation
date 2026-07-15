<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $permissions = $this->permissionService->list([
            'resource' => $request->query('resource'),
            'is_system' => $request->query('is_system'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 50),
        ]);

        return PermissionResource::collection($permissions);
    }

    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->create($request->validated());

        return response()->json([
            'message' => __('permissions.messages.created'),
            'permission' => new PermissionResource($permission),
        ], 201);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->permissionService->update($permission, $request->validated());

        return response()->json([
            'message' => __('permissions.messages.updated'),
            'permission' => new PermissionResource($permission),
        ]);
    }

    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        if (! $request->user()?->hasPermission('permission.delete')) {
            abort(403, __('permissions.messages.forbidden'));
        }

        $this->permissionService->delete($permission);

        return response()->json([
            'message' => __('permissions.messages.deleted'),
        ]);
    }
}
