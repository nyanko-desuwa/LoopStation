<?php

namespace App\Services;

use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MeasurementUnitService
{
    /**
     * @param  array{category?: string|null, is_system?: mixed, search?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = MeasurementUnit::query()->latest('id');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== null && $filters['is_system'] !== '') {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 200));

        return $query->paginate($perPage);
    }

    public function create(array $data, ?User $actor = null): MeasurementUnit
    {
        return MeasurementUnit::create([
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'category' => $data['category'],
            // API create luôn là unit custom (không system).
            'is_system' => $data['is_system'] ?? false,
            'created_by' => $actor?->id,
        ]);
    }

    public function update(MeasurementUnit $unit, array $data): MeasurementUnit
    {
        $unit->fill(collect($data)->only(['name', 'symbol', 'category'])->all());
        $unit->save();

        return $unit->refresh();
    }

    public function delete(MeasurementUnit $unit): void
    {
        if ($unit->is_system) {
            throw ValidationException::withMessages([
                'measurement_unit' => __('measurement_units.messages.system_locked'),
            ]);
        }

        // HANDOVER_* chưa implement - khi có sẽ chặn xóa nếu unit đang được dùng.
        $unit->delete();
    }
}
