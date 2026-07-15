<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\SyncRolePermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Models\RolePermission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    /**
     * GET /api/roles/{role}/permissions
     */
    public function show(Request $request, string $role): JsonResponse
    {
        $this->assertValidRole($role);

        if (! $request->user()?->hasPermission('role_permission.view')) {
            abort(403, __('permissions.messages.forbidden'));
        }

        $permissions = $this->permissionService->permissionsForRole($role);

        return response()->json([
            'role' => $role,
            'codes' => $this->permissionService->codesForRole($role),
            'permissions' => PermissionResource::collection($permissions),
        ]);
    }

    /**
     * PUT /api/roles/{role}/permissions - replace toàn bộ mapping.
     */
    public function sync(SyncRolePermissionsRequest $request, string $role): JsonResponse
    {
        $this->assertValidRole($role);

        $data = $request->validated();

        // Nếu client gửi role trong body thì phải khớp path.
        if (array_key_exists('role', $data) && $data['role'] !== $role) {
            throw ValidationException::withMessages([
                'role' => __('permissions.messages.role_mismatch'),
            ]);
        }

        $codes = $this->permissionService->syncRolePermissions(
            role: $role,
            permissionIds: $data['permission_ids'],
            actor: $request->user(),
        );

        return response()->json([
            'message' => __('permissions.messages.role_synced'),
            'role' => $role,
            'codes' => $codes,
        ]);
    }

    /**
     * GET /api/me/permissions - quyền của user đang đăng nhập.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->role ?? 'user';

        return response()->json([
            'role' => $role,
            'codes' => $this->permissionService->codesForRole($role),
        ]);
    }

    private function assertValidRole(string $role): void
    {
        if (! in_array($role, RolePermission::ROLES, true)) {
            abort(404);
        }
    }
}
