<?php

namespace App\Services;

use App\Models\User;
use App\Models\WasteType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class WasteTypeService
{
    /**
     * List loại rác.
     * - Guest / user thường: system + custom của chính họ.
     * - Có waste_type.update/delete: thấy tất cả (kể cả custom của người khác).
     *
     * @param  array{is_system?: mixed, search?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = [], ?User $viewer = null, bool $asManager = false): LengthAwarePaginator
    {
        $query = WasteType::query()->latest('id');

        if (! $asManager) {
            $query->where(function ($q) use ($viewer): void {
                $q->where('is_system', true);

                if ($viewer !== null) {
                    $q->orWhere(function ($own) use ($viewer): void {
                        $own->where('is_system', false)
                            ->where('created_by', $viewer->id);
                    });
                }
            });
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== null && $filters['is_system'] !== '') {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 200));

        return $query->paginate($perPage);
    }

    /**
     * Manager tạo loại chuẩn (is_system=true) hoặc custom tùy payload.
     * User với waste_type.create_custom tạo custom (is_system=false).
     */
    public function create(array $data, User $actor, bool $asSystem = false): WasteType
    {
        // Chỉ asSystem=true mới ghi is_system; create_custom luôn false dù client gửi true.
        return WasteType::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'is_system' => $asSystem,
            'created_by' => $actor->id,
        ]);
    }

    public function update(WasteType $wasteType, array $data): WasteType
    {
        $wasteType->fill(collect($data)->only(['name', 'icon'])->all());
        $wasteType->save();

        return $wasteType->refresh();
    }

    public function delete(WasteType $wasteType, ?User $actor = null, bool $asManager = false): void
    {
        // System type: chỉ manager (asManager) mới xóa; và vẫn soft-delete.
        if ($wasteType->is_system && ! $asManager) {
            throw ValidationException::withMessages([
                'waste_type' => __('waste_types.messages.system_locked'),
            ]);
        }

        // Custom type: owner hoặc manager.
        if (! $wasteType->is_system && ! $asManager) {
            if ($actor === null || $wasteType->created_by !== $actor->id) {
                throw ValidationException::withMessages([
                    'waste_type' => __('waste_types.messages.not_owner'),
                ]);
            }
        }

        // HANDOVER_WASTE_ITEMS chưa implement - khi có sẽ chặn nếu còn tham chiếu.
        $wasteType->delete();
    }

    /**
     * Guest/user có được xem chi tiết loại này không?
     */
    public function isVisibleTo(?User $viewer, WasteType $wasteType, bool $asManager = false): bool
    {
        if ($asManager || $wasteType->is_system) {
            return true;
        }

        return $viewer !== null && $wasteType->created_by === $viewer->id;
    }
}
