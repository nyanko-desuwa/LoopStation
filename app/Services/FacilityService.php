<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FacilityService
{
    /**
     * Danh sách cơ sở. User thường chỉ thấy active; manager có thể lọc theo status.
     *
     * @param  array{status?: string|null, type?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = [], bool $asManager = false): LengthAwarePaginator|Collection
    {
        $query = Facility::query()->latest('id');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->active();
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function create(array $data): Facility
    {
        return Facility::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'status' => $data['status'] ?? Facility::STATUS_ACTIVE,
        ]);
    }

    public function update(Facility $facility, array $data): Facility
    {
        $facility->fill($data);
        $facility->save();

        return $facility->refresh();
    }

    public function lock(Facility $facility): Facility
    {
        $facility->update(['status' => Facility::STATUS_LOCKED]);

        return $facility->refresh();
    }

    public function unlock(Facility $facility): Facility
    {
        $facility->update(['status' => Facility::STATUS_ACTIVE]);

        return $facility->refresh();
    }

    /**
     * Soft-delete. Chặn nếu còn staff/manager đang gắn vào cơ sở.
     */
    public function delete(Facility $facility): void
    {
        $staffCount = $facility->users()
            ->whereIn('role', ['staff', 'manager'])
            ->count();

        if ($staffCount > 0) {
            throw ValidationException::withMessages([
                'facility' => __('facilities.messages.has_staff'),
            ]);
        }

        $facility->delete();
    }
}
