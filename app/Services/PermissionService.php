<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermissionService
{
    public const CACHE_TTL_SECONDS = 3600;

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Permission::query()->orderBy('resource')->orderBy('action');

        if (! empty($filters['resource'])) {
            $query->where('resource', $filters['resource']);
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== null && $filters['is_system'] !== '') {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 200));

        return $query->paginate($perPage);
    }

    public function create(array $data): Permission
    {
        $resource = $data['resource'];
        $action = $data['action'];
        $code = $data['code'] ?? "{$resource}.{$action}";

        return Permission::create([
            'code' => $code,
            'resource' => $resource,
            'action' => $action,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            // Quyền tạo qua API mặc định không phải system.
            'is_system' => $data['is_system'] ?? false,
        ]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        // code / resource / action ổn định - không cho đổi sau khi seed.
        $permission->fill(collect($data)->only(['name', 'description'])->all());
        $permission->save();

        return $permission->refresh();
    }

    public function delete(Permission $permission): void
    {
        if ($permission->is_system) {
            throw ValidationException::withMessages([
                'permission' => __('permissions.messages.system_locked'),
            ]);
        }

        $permission->delete();
        $this->flushAllRoleCaches();
    }

    /**
     * Danh sách code quyền của 1 role (cache 1 giờ).
     *
     * @return list<string>
     */
    public function codesForRole(string $role): array
    {
        return Cache::remember(
            $this->roleCacheKey($role),
            self::CACHE_TTL_SECONDS,
            function () use ($role): array {
                return RolePermission::query()
                    ->where('role', $role)
                    ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                    ->orderBy('permissions.code')
                    ->pluck('permissions.code')
                    ->unique()
                    ->values()
                    ->all();
            }
        );
    }

    public function roleHas(string $role, string $code): bool
    {
        return in_array($code, $this->codesForRole($role), true);
    }

    public function userHas(User $user, string $code): bool
    {
        $role = $user->role ?? 'user';

        return $this->roleHas($role, $code);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissionsForRole(string $role): Collection
    {
        return Permission::query()
            ->whereIn('code', $this->codesForRole($role))
            ->orderBy('resource')
            ->orderBy('action')
            ->get();
    }

    /**
     * Thay toàn bộ mapping của 1 role bằng danh sách permission_ids.
     *
     * @param  list<int>  $permissionIds
     * @return list<string> codes sau khi sync
     */
    public function syncRolePermissions(string $role, array $permissionIds, ?User $actor = null): array
    {
        if (! in_array($role, RolePermission::ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => __('permissions.messages.invalid_role'),
            ]);
        }

        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        $existingCount = Permission::query()->whereIn('id', $permissionIds)->count();
        if ($existingCount !== count($permissionIds)) {
            throw ValidationException::withMessages([
                'permission_ids' => __('permissions.messages.invalid_permission_ids'),
            ]);
        }

        DB::transaction(function () use ($role, $permissionIds, $actor): void {
            RolePermission::query()->where('role', $role)->delete();

            $now = now();
            $rows = array_map(
                fn (int $id) => [
                    'role' => $role,
                    'permission_id' => $id,
                    'created_by' => $actor?->id,
                    'created_at' => $now,
                ],
                $permissionIds
            );

            if ($rows !== []) {
                RolePermission::query()->insert($rows);
            }
        });

        $this->flushRoleCache($role);

        return $this->codesForRole($role);
    }

    public function grant(string $role, int $permissionId, ?User $actor = null): RolePermission
    {
        if (! in_array($role, RolePermission::ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => __('permissions.messages.invalid_role'),
            ]);
        }

        if (! Permission::query()->whereKey($permissionId)->exists()) {
            throw ValidationException::withMessages([
                'permission_id' => __('permissions.messages.invalid_permission_ids'),
            ]);
        }

        $row = RolePermission::query()->firstOrCreate(
            [
                'role' => $role,
                'permission_id' => $permissionId,
            ],
            [
                'created_by' => $actor?->id,
            ]
        );

        $this->flushRoleCache($role);

        return $row;
    }

    public function revoke(string $role, int $permissionId): void
    {
        RolePermission::query()
            ->where('role', $role)
            ->where('permission_id', $permissionId)
            ->delete();

        $this->flushRoleCache($role);
    }

    public function flushRoleCache(string $role): void
    {
        Cache::forget($this->roleCacheKey($role));
    }

    public function flushAllRoleCaches(): void
    {
        foreach (RolePermission::ROLES as $role) {
            $this->flushRoleCache($role);
        }
    }

    private function roleCacheKey(string $role): string
    {
        return "rbac.role.{$role}.codes";
    }
}
