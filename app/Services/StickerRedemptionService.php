<?php

namespace App\Services;

use App\Models\Sticker;
use App\Models\StickerRedemption;
use App\Models\StickerRedemptionItem;
use App\Models\StickerRewardItem;
use App\Models\StickerRewardRule;
use App\Models\User;
use App\Models\UserSticker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StickerRedemptionService
{
    /**
     * @param  array{status?: string|null, per_page?: int}  $filters
     */
    public function list(User $viewer, array $filters = []): LengthAwarePaginator
    {
        $query = StickerRedemption::query()
            ->with(['sticker.set', 'items', 'user'])
            ->latest('id');

        if ($viewer->hasPermission('sticker_redemption.view')) {
            // staff/manager: all
        } else {
            $query->where('user_id', $viewer->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function findVisible(StickerRedemption $redemption, User $viewer): StickerRedemption
    {
        if ($redemption->user_id !== $viewer->id && ! $viewer->hasPermission('sticker_redemption.view')) {
            abort(404);
        }

        return $redemption->load(['sticker.set', 'items', 'user', 'facility', 'staff']);
    }

    /**
     * Đổi sticker vật lý: trừ user_stickers.quantity theo redeem_quantity_required,
     * đọc rules active → trừ stock từng item + snapshot, tất cả trong 1 transaction.
     *
     * @param  array{
     *   sticker_id: int,
     *   fulfillment_method: string,
     *   facility_id?: int|null,
     *   recipient_name?: string|null,
     *   recipient_phone?: string|null,
     *   shipping_address?: string|null,
     *   shipping_note?: string|null
     * }  $data
     */
    public function create(User $user, array $data): StickerRedemption
    {
        $method = $data['fulfillment_method'] ?? StickerRedemption::METHOD_PICKUP;

        if (! in_array($method, StickerRedemption::METHODS, true)) {
            throw ValidationException::withMessages([
                'fulfillment_method' => __('sticker_rewards.messages.invalid_method'),
            ]);
        }

        if ($method === StickerRedemption::METHOD_PICKUP && empty($data['facility_id'])) {
            throw ValidationException::withMessages([
                'facility_id' => __('sticker_rewards.messages.facility_required'),
            ]);
        }

        if ($method === StickerRedemption::METHOD_DELIVERY) {
            foreach (['recipient_name', 'recipient_phone', 'shipping_address'] as $field) {
                if (empty($data[$field])) {
                    throw ValidationException::withMessages([
                        $field => __('sticker_rewards.messages.delivery_fields_required'),
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($user, $data, $method): StickerRedemption {
            $sticker = Sticker::query()->whereKey($data['sticker_id'])->lockForUpdate()->first();

            if ($sticker === null || $sticker->trashed()) {
                throw ValidationException::withMessages([
                    'sticker_id' => __('sticker_rewards.messages.invalid_sticker'),
                ]);
            }

            // Rule active của sticker: quyết định sticker này đổi được gì.
            $rules = StickerRewardRule::query()
                ->with('rewardItem')
                ->where('sticker_id', $sticker->id)
                ->active()
                ->get();

            if ($rules->isEmpty()) {
                throw ValidationException::withMessages([
                    'sticker_id' => __('sticker_rewards.messages.no_reward_rule'),
                ]);
            }

            // Số sticker ảo cần để đổi 1 lần, chốt theo cấu hình hiện tại.
            $required = max(1, (int) $sticker->redeem_quantity_required);

            $inventory = UserSticker::query()
                ->where('user_id', $user->id)
                ->where('sticker_id', $sticker->id)
                ->lockForUpdate()
                ->first();

            if ($inventory === null || $inventory->quantity < $required) {
                throw ValidationException::withMessages([
                    'sticker_id' => __('sticker_rewards.messages.not_enough_stickers', [
                        'required' => $required,
                    ]),
                ]);
            }

            // Khóa + kiểm tra tồn kho từng vật phẩm trước khi trừ.
            $plan = [];
            foreach ($rules as $rule) {
                $item = StickerRewardItem::query()->whereKey($rule->reward_item_id)->lockForUpdate()->first();

                if ($item === null || $item->trashed() || ! $item->isActive()) {
                    throw ValidationException::withMessages([
                        'reward_item' => __('sticker_rewards.messages.item_unavailable', [
                            'name' => $rule->rewardItem?->name ?? '#'.$rule->reward_item_id,
                        ]),
                    ]);
                }

                $needed = max(1, (int) $rule->quantity);
                if (! $item->hasStock($needed)) {
                    throw ValidationException::withMessages([
                        'reward_item' => __('sticker_rewards.messages.item_out_of_stock', [
                            'name' => $item->name,
                        ]),
                    ]);
                }

                $plan[] = ['item' => $item, 'quantity' => $needed];
            }

            // Trừ sticker ảo của user.
            $inventory->decrement('quantity', $required);

            $redemption = StickerRedemption::create([
                'user_id' => $user->id,
                'sticker_id' => $sticker->id,
                'quantity_used' => $required,
                'fulfillment_method' => $method,
                'status' => StickerRedemption::STATUS_PENDING,
                'facility_id' => $method === StickerRedemption::METHOD_PICKUP ? ($data['facility_id'] ?? null) : null,
                'recipient_name' => $method === StickerRedemption::METHOD_DELIVERY ? ($data['recipient_name'] ?? null) : null,
                'recipient_phone' => $method === StickerRedemption::METHOD_DELIVERY ? ($data['recipient_phone'] ?? null) : null,
                'shipping_address' => $method === StickerRedemption::METHOD_DELIVERY ? ($data['shipping_address'] ?? null) : null,
                'shipping_note' => $data['shipping_note'] ?? null,
            ]);

            // Trừ stock + snapshot từng vật phẩm.
            foreach ($plan as $line) {
                /** @var StickerRewardItem $item */
                $item = $line['item'];
                $qty = $line['quantity'];

                $item->decrement('stock', $qty);

                StickerRedemptionItem::create([
                    'redemption_id' => $redemption->id,
                    'reward_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_image_url' => $item->image_url,
                    'quantity' => $qty,
                ]);
            }

            return $redemption->fresh()->load(['sticker.set', 'items', 'user']);
        });
    }

    public function markShipping(StickerRedemption $redemption, User $actor): StickerRedemption
    {
        if ($redemption->fulfillment_method !== StickerRedemption::METHOD_DELIVERY) {
            throw ValidationException::withMessages([
                'redemption' => __('sticker_rewards.messages.not_delivery'),
            ]);
        }

        if (! $redemption->isPending()) {
            throw ValidationException::withMessages([
                'redemption' => __('sticker_rewards.messages.not_pending'),
            ]);
        }

        $redemption->update([
            'status' => StickerRedemption::STATUS_SHIPPING,
            'staff_id' => $actor->id,
        ]);

        return $redemption->refresh()->load(['sticker.set', 'items']);
    }

    public function fulfill(StickerRedemption $redemption, User $actor): StickerRedemption
    {
        if ($redemption->isTerminal()) {
            throw ValidationException::withMessages([
                'redemption' => __('sticker_rewards.messages.already_closed'),
            ]);
        }

        $redemption->update([
            'status' => StickerRedemption::STATUS_FULFILLED,
            'staff_id' => $actor->id,
        ]);

        return $redemption->refresh()->load(['sticker.set', 'items', 'staff']);
    }

    /**
     * Hủy + hoàn sticker ảo + hoàn stock vật phẩm.
     */
    public function cancel(StickerRedemption $redemption, User $actor): StickerRedemption
    {
        $isOwner = $redemption->user_id === $actor->id;
        $canStaffCancel = $actor->hasPermission('sticker_redemption.cancel')
            || $actor->hasPermission('sticker_redemption.fulfill');

        if (! $isOwner && ! $canStaffCancel) {
            abort(403);
        }

        if (! $redemption->isCancellable()) {
            throw ValidationException::withMessages([
                'redemption' => __('sticker_rewards.messages.not_cancellable'),
            ]);
        }

        return DB::transaction(function () use ($redemption): StickerRedemption {
            $locked = StickerRedemption::query()->whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                throw ValidationException::withMessages([
                    'redemption' => __('sticker_rewards.messages.not_cancellable'),
                ]);
            }

            // Hoàn sticker ảo.
            $inventory = UserSticker::query()
                ->where('user_id', $locked->user_id)
                ->where('sticker_id', $locked->sticker_id)
                ->lockForUpdate()
                ->first();

            if ($inventory !== null) {
                $inventory->increment('quantity', $locked->quantity_used);
            } else {
                UserSticker::create([
                    'user_id' => $locked->user_id,
                    'sticker_id' => $locked->sticker_id,
                    'quantity' => $locked->quantity_used,
                    'total_obtained' => $locked->quantity_used,
                    'first_obtained_at' => now(),
                    'last_obtained_at' => now(),
                ]);
            }

            // Hoàn stock vật phẩm theo snapshot (item còn tồn tại).
            foreach ($locked->items as $line) {
                if ($line->reward_item_id === null) {
                    continue;
                }
                $item = StickerRewardItem::query()->whereKey($line->reward_item_id)->lockForUpdate()->first();
                if ($item !== null) {
                    $item->increment('stock', $line->quantity);
                }
            }

            $locked->update(['status' => StickerRedemption::STATUS_CANCELLED]);

            return $locked->refresh()->load(['sticker.set', 'items']);
        });
    }
}
