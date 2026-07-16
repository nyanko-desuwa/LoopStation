<?php

namespace App\Services;

use App\Models\Sticker;
use App\Models\StickerRewardItem;
use App\Models\StickerRewardRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StickerRewardService
{
    /**
     * Danh mục vật phẩm quà. Manager thấy full; user chỉ active (để xem đổi được gì).
     *
     * @param  array{status?: string|null, search?: string|null, per_page?: int}  $filters
     */
    public function listItems(array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = StickerRewardItem::query()->latest('id');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
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

    public function findItemVisible(StickerRewardItem $item, bool $asManager = false): StickerRewardItem
    {
        if (! $asManager && ! $item->isActive()) {
            abort(404);
        }

        return $item;
    }

    /**
     * @param  array{name: string, image_url?: string|null, description?: string|null, stock?: int, status?: string}  $data
     */
    public function createItem(array $data): StickerRewardItem
    {
        return StickerRewardItem::create([
            'name' => $data['name'],
            'image_url' => $data['image_url'] ?? null,
            'description' => $data['description'] ?? null,
            'stock' => max(0, (int) ($data['stock'] ?? 0)),
            'status' => $data['status'] ?? StickerRewardItem::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array{name?: string, image_url?: string|null, description?: string|null, stock?: int, status?: string}  $data
     */
    public function updateItem(StickerRewardItem $item, array $data): StickerRewardItem
    {
        $payload = collect($data)->only([
            'name', 'image_url', 'description', 'stock', 'status',
        ])->all();

        if (isset($payload['stock'])) {
            $payload['stock'] = max(0, (int) $payload['stock']);
        }

        $item->fill($payload);
        $item->save();

        return $item->refresh();
    }

    public function deleteItem(StickerRewardItem $item): void
    {
        $item->delete();
    }

    /**
     * Chỉnh tồn kho tuyệt đối (manager, quyền adjust_stock riêng).
     */
    public function adjustStock(StickerRewardItem $item, int $stock): StickerRewardItem
    {
        $item->update(['stock' => max(0, $stock)]);

        return $item->refresh();
    }

    /**
     * Rule của 1 sticker: sticker đó đổi ra vật phẩm nào.
     *
     * @param  array{status?: string|null, per_page?: int}  $filters
     */
    public function listRules(Sticker $sticker, array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = StickerRewardRule::query()
            ->with('rewardItem')
            ->where('sticker_id', $sticker->id)
            ->latest('id');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->active();
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    /**
     * @param  array{reward_item_id: int, quantity?: int, status?: string}  $data
     */
    public function createRule(Sticker $sticker, array $data): StickerRewardRule
    {
        $item = StickerRewardItem::query()->find((int) $data['reward_item_id']);
        if ($item === null || $item->trashed()) {
            throw ValidationException::withMessages([
                'reward_item_id' => __('sticker_rewards.messages.item_not_found'),
            ]);
        }

        $exists = StickerRewardRule::query()
            ->where('sticker_id', $sticker->id)
            ->where('reward_item_id', $item->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reward_item_id' => __('sticker_rewards.messages.rule_exists'),
            ]);
        }

        return StickerRewardRule::create([
            'sticker_id' => $sticker->id,
            'reward_item_id' => $item->id,
            'quantity' => max(1, (int) ($data['quantity'] ?? 1)),
            'status' => $data['status'] ?? StickerRewardRule::STATUS_ACTIVE,
        ])->load('rewardItem');
    }

    /**
     * @param  array{quantity?: int, status?: string}  $data
     */
    public function updateRule(StickerRewardRule $rule, array $data): StickerRewardRule
    {
        $payload = collect($data)->only(['quantity', 'status'])->all();

        if (isset($payload['quantity'])) {
            $payload['quantity'] = max(1, (int) $payload['quantity']);
        }

        $rule->fill($payload);
        $rule->save();

        return $rule->refresh()->load('rewardItem');
    }

    public function deleteRule(StickerRewardRule $rule): void
    {
        $rule->delete();
    }
}
