<?php

namespace App\Services;

use App\Models\RewardCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RewardCatalogService
{
    /**
     * @param  array{status?: string|null, search?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = RewardCatalog::query()->latest('id');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            // Portal user: chỉ active (kể cả stock = 0 để hiện "hết hàng").
            $query->active();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function create(array $data): RewardCatalog
    {
        return RewardCatalog::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'points_cost' => $data['points_cost'],
            'stock' => $data['stock'] ?? 0,
            'status' => $data['status'] ?? RewardCatalog::STATUS_ACTIVE,
        ]);
    }

    public function update(RewardCatalog $reward, array $data): RewardCatalog
    {
        $reward->fill(collect($data)->only([
            'name',
            'description',
            'image_url',
            'points_cost',
            'stock',
            'status',
        ])->all());
        $reward->save();

        return $reward->refresh();
    }

    public function delete(RewardCatalog $reward): void
    {
        $reward->delete();
    }
}
