<?php

namespace App\Services;

use App\Models\PointEarned;
use App\Models\PointSpent;
use App\Models\Redemption;
use App\Models\RewardCatalog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedemptionService
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * @param  array{status?: string|null, per_page?: int}  $filters
     */
    public function list(User $viewer, array $filters = []): LengthAwarePaginator
    {
        $query = Redemption::query()
            ->with(['reward', 'user'])
            ->latest('id');

        if ($viewer->hasPermission('redemption.view')) {
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

    public function findVisible(Redemption $redemption, User $viewer): Redemption
    {
        if ($redemption->user_id !== $viewer->id && ! $viewer->hasPermission('redemption.view')) {
            abort(404);
        }

        return $redemption->load(['reward', 'user', 'fulfilledBy']);
    }

    /**
     * Đổi quà: lock reward + spend points + giảm stock trong 1 transaction.
     *
     * @param  array{
     *   reward_id: int,
     *   quantity?: int,
     *   fulfillment_method: string,
     *   recipient_name?: string|null,
     *   recipient_phone?: string|null,
     *   shipping_address?: string|null,
     *   shipping_note?: string|null
     * }  $data
     */
    public function create(User $user, array $data): Redemption
    {
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $method = $data['fulfillment_method'] ?? Redemption::METHOD_PICKUP;

        if (! in_array($method, Redemption::METHODS, true)) {
            throw ValidationException::withMessages([
                'fulfillment_method' => __('redemptions.messages.invalid_method'),
            ]);
        }

        if ($method === Redemption::METHOD_DELIVERY) {
            foreach (['recipient_name', 'recipient_phone', 'shipping_address'] as $field) {
                if (empty($data[$field])) {
                    throw ValidationException::withMessages([
                        $field => __('redemptions.messages.delivery_fields_required'),
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($user, $data, $quantity, $method): Redemption {
            $reward = RewardCatalog::query()
                ->whereKey($data['reward_id'])
                ->lockForUpdate()
                ->first();

            if ($reward === null || $reward->trashed()) {
                throw ValidationException::withMessages([
                    'reward_id' => __('redemptions.messages.invalid_reward'),
                ]);
            }

            if (! $reward->isActive()) {
                throw ValidationException::withMessages([
                    'reward_id' => __('redemptions.messages.reward_locked'),
                ]);
            }

            if (! $reward->hasStock($quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => __('redemptions.messages.out_of_stock'),
                ]);
            }

            $pointsSpent = $reward->points_cost * $quantity;

            // Tạo redemption pending trước để có id (reference_id cho POINT_SPENT).
            $redemption = Redemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $pointsSpent,
                'quantity' => $quantity,
                'status' => Redemption::STATUS_PENDING,
                'fulfillment_method' => $method,
                'recipient_name' => $method === Redemption::METHOD_DELIVERY ? ($data['recipient_name'] ?? null) : null,
                'recipient_phone' => $method === Redemption::METHOD_DELIVERY ? ($data['recipient_phone'] ?? null) : null,
                'shipping_address' => $method === Redemption::METHOD_DELIVERY ? ($data['shipping_address'] ?? null) : null,
                'shipping_note' => $data['shipping_note'] ?? null,
            ]);

            $spent = $this->walletService->spend($user, $pointsSpent, [
                'source_type' => PointSpent::SOURCE_REDEMPTION,
                'reference_id' => $redemption->id,
                'description' => __('redemptions.messages.spend_description', [
                    'name' => $reward->name,
                    'qty' => $quantity,
                ]),
            ]);

            $redemption->update(['transaction_id' => $spent->id]);
            $reward->decrement('stock', $quantity);

            return $redemption->fresh()->load(['reward', 'user']);
        });
    }

    public function markShipping(Redemption $redemption, User $actor): Redemption
    {
        if ($redemption->fulfillment_method !== Redemption::METHOD_DELIVERY) {
            throw ValidationException::withMessages([
                'redemption' => __('redemptions.messages.not_delivery'),
            ]);
        }

        if (! $redemption->isPending()) {
            throw ValidationException::withMessages([
                'redemption' => __('redemptions.messages.not_pending'),
            ]);
        }

        $redemption->update(['status' => Redemption::STATUS_SHIPPING]);

        return $redemption->refresh();
    }

    public function fulfill(Redemption $redemption, User $actor): Redemption
    {
        if ($redemption->isTerminal()) {
            throw ValidationException::withMessages([
                'redemption' => __('redemptions.messages.already_closed'),
            ]);
        }

        // delivery nên qua shipping trước (khuyến nghị, không bắt buộc cứng).
        $redemption->update([
            'status' => Redemption::STATUS_FULFILLED,
            'fulfilled_by_id' => $actor->id,
        ]);

        return $redemption->refresh()->load(['reward', 'fulfilledBy']);
    }

    /**
     * Hủy + hoàn điểm + hoàn stock.
     */
    public function cancel(Redemption $redemption, User $actor): Redemption
    {
        $isOwner = $redemption->user_id === $actor->id;
        $canStaffCancel = $actor->hasPermission('redemption.cancel')
            || $actor->hasPermission('redemption.fulfill');

        if (! $isOwner && ! $canStaffCancel) {
            abort(403);
        }

        if (! $redemption->isCancellable()) {
            throw ValidationException::withMessages([
                'redemption' => __('redemptions.messages.not_cancellable'),
            ]);
        }

        return DB::transaction(function () use ($redemption): Redemption {
            $redemption = Redemption::query()->whereKey($redemption->id)->lockForUpdate()->firstOrFail();
            $reward = RewardCatalog::query()->whereKey($redemption->reward_id)->lockForUpdate()->first();

            if ($redemption->isTerminal()) {
                throw ValidationException::withMessages([
                    'redemption' => __('redemptions.messages.not_cancellable'),
                ]);
            }

            // Hoàn điểm.
            $this->walletService->earn($redemption->user, $redemption->points_spent, [
                'source_type' => PointEarned::SOURCE_REDEMPTION_REFUND,
                'reference_id' => $redemption->id,
                'description' => __('redemptions.messages.refund_description', [
                    'id' => $redemption->id,
                ]),
            ]);

            if ($reward !== null) {
                $reward->increment('stock', $redemption->quantity);
            }

            $redemption->update(['status' => Redemption::STATUS_CANCELLED]);

            return $redemption->refresh()->load('reward');
        });
    }
}
